<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Unit;

use DeepWebSolutions\PluginTemplate\Component\AdminNotice;
use DeepWebSolutions\PluginTemplate\Component\ExampleSettings;
use DeepWebSolutions\PluginTemplate\Feature\GenericFeature;
use DeepWebSolutions\PluginTemplate\Feature\WooCommerceFeature;
use DeepWebSolutions\PluginTemplate\Installer\Installer;
use DeepWebSolutions\PluginTemplate\Plugin;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Utilities\AdminNotices\AdminNoticesService;
use DeepWebSolutions\PluginTemplate\Scoped\DI\Container;
use DeepWebSolutions\PluginTemplate\Tests\Unit\Doubles\ThrowingInstaller;
use DeepWebSolutions\PluginTemplate\Tests\Unit\Doubles\ThrowingStore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap-wp-stubs.php';

#[CoversClass( Plugin::class )]
#[UsesClass( GenericFeature::class )]
#[UsesClass( WooCommerceFeature::class )]
#[UsesClass( Installer::class )]
#[UsesClass( AdminNotice::class )]
#[UsesClass( ExampleSettings::class )]
final class InstallerLifecycleTest extends TestCase {
	protected function setUp(): void {
		WordPressStubState::reset();
		( new \ReflectionProperty( Plugin::class, 'instance' ) )->setValue( null, null );
	}

	public function test_update_advances_the_stored_version_without_writing_future_schema_state(): void {
		// Pre-seed an older stored version so the kernel takes the update() path on boot.
		WordPressStubState::$options['dws_plugin_template'] = array(
			'version'      => '1.0.0',
			'installed_at' => 123,
		);

		Plugin::get_instance()->boot();

		$state = WordPressStubState::option_array( 'dws_plugin_template' );
		self::assertSame( '2.0.0', $state['version'] ?? null, 'The update path must advance the stored version to current.' );
		self::assertArrayNotHasKey(
			'schema',
			$state,
			'The example migration must not execute a step gated above the plugin version, which would write future-version state.'
		);
	}

	public function test_update_is_idempotent_across_reboots_and_no_ops_at_the_current_version(): void {
		WordPressStubState::$options['dws_plugin_template'] = array(
			'version'      => '1.0.0',
			'installed_at' => 123,
		);

		Plugin::get_instance()->boot();
		$after_update = WordPressStubState::option_array( 'dws_plugin_template' );

		// A second boot resolves the stored version as current, so the kernel takes neither install nor update.
		( new \ReflectionProperty( Plugin::class, 'instance' ) )->setValue( null, null );
		Plugin::get_instance()->boot();

		self::assertSame(
			$after_update,
			WordPressStubState::option_array( 'dws_plugin_template' ),
			'Re-running the lifecycle at the current version must converge on identical state.'
		);
	}

	public function test_a_failed_install_stops_boot_and_queues_a_persistent_notice(): void {
		$plugin    = Plugin::get_instance();
		$container = $plugin->get_container();
		self::assertInstanceOf( Container::class, $container );
		$container->set( Installer::class, new ThrowingInstaller() );

		$plugin->boot();

		// The boot stopped before any component: the generic Feature's notice callback never registered.
		self::assertFalse(
			WordPressStubState::has_object_action( 'admin_notices', AdminNotice::class, 'render' ),
			'A failed install must stop the boot before any component registers hooks.'
		);

		// Notice rendering was wired before the kernel ran, so the queued failure notice can still surface.
		self::assertTrue(
			WordPressStubState::has_object_action( 'admin_notices', AdminNoticesService::class, 'render_notices' ),
			'Notice rendering must stay wired so the failure notice renders despite the stopped boot.'
		);

		// The failure was queued as a persistent notice in the wp_options-backed store.
		self::assertArrayHasKey(
			$this->install_failure_notice_id(),
			WordPressStubState::option_array( 'dws_plugin_template_notices' ),
			'A failed install must queue a persistent admin notice.'
		);
	}

	public function test_a_recovered_boot_clears_the_stale_install_failure_notice(): void {
		$failing   = Plugin::get_instance();
		$container = $failing->get_container();
		self::assertInstanceOf( Container::class, $container );
		$container->set( Installer::class, new ThrowingInstaller() );
		$failing->boot();

		$notice_id = $this->install_failure_notice_id();
		self::assertArrayHasKey( $notice_id, WordPressStubState::option_array( 'dws_plugin_template_notices' ) );

		// A later request reuses the persisted notice option but a fresh singleton with the working installer.
		( new \ReflectionProperty( Plugin::class, 'instance' ) )->setValue( null, null );
		Plugin::get_instance()->boot();

		self::assertArrayNotHasKey(
			$notice_id,
			WordPressStubState::option_array( 'dws_plugin_template_notices' ),
			'A boot whose install succeeds must drop the stale failure notice.'
		);
	}

	public function test_uninstall_removes_the_full_single_site_footprint(): void {
		WordPressStubState::$options = array(
			'dws_plugin_template'                => array( 'version' => '2.0.0', 'installed_at' => 123 ),
			'dws_plugin_template_notices'        => array( 'some-id' => array( 'id' => 'some-id', 'message' => 'x' ) ),
			'dws_plugin_template_enable_feature' => 'yes',
			'dws_plugin_template_api_key'        => 'secret',
			'unrelated_plugin_option'            => 'keep me',
		);

		Plugin::get_instance()->get_installer()->uninstall();

		foreach ( array(
			'dws_plugin_template',
			'dws_plugin_template_notices',
			'dws_plugin_template_enable_feature',
			'dws_plugin_template_api_key',
		) as $option ) {
			self::assertArrayNotHasKey( $option, WordPressStubState::$options, "uninstall() must remove $option." );
		}

		self::assertArrayHasKey(
			'unrelated_plugin_option',
			WordPressStubState::$options,
			'uninstall() must remove only the plugin footprint, leaving foreign options intact.'
		);

		self::assertContains(
			'dws_plugin_template_dismissed_notices',
			WordPressStubState::$deleted_user_meta,
			'uninstall() must delete the per-user dismissal records.'
		);
	}

	public function test_boot_wires_the_notice_dismiss_transport(): void {
		Plugin::get_instance()->boot();

		self::assertTrue(
			WordPressStubState::has_object_action( 'admin_footer', AdminNoticesService::class, 'print_dismiss_script' ),
			'Boot must wire the dismiss script onto admin_footer.'
		);
		self::assertTrue(
			WordPressStubState::has_object_action(
				'wp_ajax_dws_plugin_template_dismiss_notice',
				AdminNoticesService::class,
				'handle_dismiss'
			),
			'Boot must wire the per-user dismiss AJAX handler.'
		);
	}

	public function test_a_network_uninstall_clears_every_site_and_restores_the_blog_context(): void {
		WordPressStubState::$is_multisite = true;
		WordPressStubState::$sites        = array( 1, 2 );
		WordPressStubState::$options      = array(
			'dws_plugin_template'                => array( 'version' => '2.0.0' ),
			'dws_plugin_template_notices'        => array( 'n' => array( 'id' => 'n', 'message' => 'x' ) ),
			'dws_plugin_template_enable_feature' => 'yes',
			'dws_plugin_template_api_key'        => 'secret',
		);

		Plugin::get_instance()->get_installer()->uninstall();

		// switch then restore, once per site and in site order, proves per-site iteration with paired teardown.
		self::assertSame(
			array( 'switch:1', 'restore', 'switch:2', 'restore' ),
			WordPressStubState::$blog_switches,
			'A network uninstall must visit every site and restore the blog context after each.'
		);
		foreach ( array(
			'dws_plugin_template',
			'dws_plugin_template_notices',
			'dws_plugin_template_enable_feature',
			'dws_plugin_template_api_key',
		) as $option ) {
			self::assertArrayNotHasKey( $option, WordPressStubState::$options, "A network uninstall must remove $option." );
		}
	}

	public function test_a_network_uninstall_restores_the_blog_context_when_a_site_deletion_throws(): void {
		WordPressStubState::$is_multisite = true;
		WordPressStubState::$sites        = array( 1, 2 );

		$installer = new Installer( new ThrowingStore(), array( 'dws_plugin_template_notices' ), array() );

		try {
			$installer->uninstall();
			self::fail( 'A throwing site deletion must propagate out of uninstall().' );
		} catch ( \RuntimeException ) {
			// The throw is expected; the assertion below proves the context was restored before it propagated.
		}

		// The first site switched then restored despite the throw, and the loop stopped rather than reaching site 2.
		self::assertSame(
			array( 'switch:1', 'restore' ),
			WordPressStubState::$blog_switches,
			'The finally must restore the blog context after a throwing deletion before the throwable propagates.'
		);
	}

	protected function install_failure_notice_id(): string {
		return (string) ( new \ReflectionClassConstant( Plugin::class, 'INSTALL_FAILURE_NOTICE' ) )->getValue();
	}
}
