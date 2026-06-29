<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Unit;

use DeepWebSolutions\PluginTemplate\Component\AdminNotice;
use DeepWebSolutions\PluginTemplate\Component\ExampleSettings;
use DeepWebSolutions\PluginTemplate\Feature\GenericFeature;
use DeepWebSolutions\PluginTemplate\Feature\WooCommerceFeature;
use DeepWebSolutions\PluginTemplate\Installer\Installer;
use DeepWebSolutions\PluginTemplate\Plugin;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\PluginKernel;
use DeepWebSolutions\PluginTemplate\Scoped\DI\Container;
use DeepWebSolutions\PluginTemplate\Tests\Unit\Doubles\SpyInstaller;
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
final class PluginBootTest extends TestCase {
	protected function setUp(): void {
		WordPressStubState::reset();

		$instance = new \ReflectionProperty( Plugin::class, 'instance' );
		$instance->setValue( null, null );
	}

	public function test_boot_runs_the_installer_and_generic_feature_without_woocommerce(): void {
		// WooCommerce inactive: is_plugin_active() returns false (no active plugins seeded) and WC_VERSION
		// may be undefined, so the WooCommerce-gated Feature must contribute nothing while the rest boots.
		Plugin::get_instance()->boot();

		// The installer ran on a fresh site: install() seeded the marker, set_stored_version() the version.
		$installer_state = WordPressStubState::option_array( 'dws_plugin_template' );
		self::assertSame( '2.0.0', $installer_state['version'] ?? null );
		self::assertArrayHasKey( 'installed_at', $installer_state );

		// The generic Feature's own component registered its callback (not merely the notice renderer).
		self::assertTrue(
			WordPressStubState::has_object_action( 'admin_notices', AdminNotice::class, 'render' ),
			'GenericFeature must register the AdminNotice render callback.'
		);

		// The WooCommerce Feature gated out: its settings tab is never registered.
		self::assertFalse(
			WordPressStubState::has_filter( 'woocommerce_get_settings_pages' ),
			'The WooCommerce settings tab must not register while WooCommerce is inactive.'
		);
	}

	public function test_boot_registers_the_woocommerce_feature_when_woocommerce_is_active(): void {
		WordPressStubState::$active_plugins = array( 'woocommerce/woocommerce.php' );
		if ( ! \defined( 'WC_VERSION' ) ) {
			\define( 'WC_VERSION', '9.0.0' );
		}

		Plugin::get_instance()->boot();

		// The gate passes both conditionals, so ExampleSettings resolves and defers its tab registration.
		self::assertTrue(
			WordPressStubState::has_object_action( 'init', ExampleSettings::class, 'register_settings_page' ),
			'The WooCommerce Feature must register ExampleSettings when WooCommerce is active.'
		);
	}

	public function test_boot_is_idempotent(): void {
		$plugin = Plugin::get_instance();
		$plugin->boot();
		$registered = \count( WordPressStubState::$actions );
		$plugin->boot();

		self::assertCount(
			$registered,
			WordPressStubState::$actions,
			'A second boot() must be a no-op and not re-register hooks.'
		);
	}

	public function test_register_lifecycle_hooks_wires_activation_and_deactivation(): void {
		PluginKernel::register_lifecycle_hooks( Plugin::get_instance() );

		self::assertCount( 1, WordPressStubState::$activation_hooks );
		self::assertCount( 1, WordPressStubState::$deactivation_hooks );
	}

	public function test_the_lifecycle_hooks_route_to_the_installer(): void {
		$plugin    = Plugin::get_instance();
		$container = $plugin->get_container();
		self::assertInstanceOf( Container::class, $container );

		$spy = new SpyInstaller();
		$container->set( Installer::class, $spy );

		PluginKernel::register_lifecycle_hooks( $plugin );

		$activate = WordPressStubState::$activation_hooks[0]['callback'];
		self::assertIsCallable( $activate );
		$activate( true );

		$deactivate = WordPressStubState::$deactivation_hooks[0]['callback'];
		self::assertIsCallable( $deactivate );
		$deactivate( true );

		self::assertSame(
			array( true ),
			$spy->activations,
			'The activation hook must invoke the installer activate() with the network flag.'
		);
		self::assertSame(
			array( true ),
			$spy->deactivations,
			'The deactivation hook must invoke the installer deactivate() with the network flag.'
		);
	}
}
