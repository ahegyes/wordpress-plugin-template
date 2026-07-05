<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration;

use DeepWebSolutions\PluginTemplate\Installer\Installer;
use DeepWebSolutions\PluginTemplate\Plugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass( Installer::class )]
#[UsesClass( Plugin::class )]
final class UninstallTest extends TestCase {
	private const OPTION_KEYS = array(
		'dws_plugin_template',
		'dws_plugin_template_notices',
		'dws_plugin_template_enable_feature',
		'dws_plugin_template_greeting',
		'dws_plugin_template-general',
	);
	private const META_KEY    = 'dws_plugin_template_dismissed_notices';

	protected function tearDown(): void {
		foreach ( self::OPTION_KEYS as $key ) {
			\delete_option( $key );
		}
		\delete_metadata( 'user', 0, self::META_KEY, '', true );
		parent::tearDown();
	}

	public function test_uninstall_removes_every_persistent_footprint_on_a_single_site(): void {
		\update_option(
			'dws_plugin_template',
			array(
				'version'      => '2.0.0',
				'installed_at' => 123,
			)
		);
		\update_option(
			'dws_plugin_template_notices',
			array(
				'n' => array(
					'id'      => 'n',
					'message' => 'x',
				),
			)
		);
		\update_option( 'dws_plugin_template_enable_feature', 'yes' );
		\update_option( 'dws_plugin_template_greeting', 'Welcome!' );
		\update_option( 'dws_plugin_template-general', array( 'welcome_text' => 'Hi' ) );
		\update_user_meta( 1, self::META_KEY, array( 'n' => true ) );

		Plugin::get_instance()->get_installer()->uninstall();

		foreach ( self::OPTION_KEYS as $key ) {
			self::assertFalse( \get_option( $key ), "uninstall() must remove the $key option row." );
		}
		self::assertSame(
			'',
			\get_user_meta( 1, self::META_KEY, true ),
			'uninstall() must remove the per-user dismissal records.'
		);
	}

	public function test_uninstall_clears_the_footprint_on_every_site_of_a_network(): void {
		if ( ! \is_multisite() ) {
			self::markTestSkipped( 'Network-wide uninstall requires a multisite environment.' );
		}

		$network = \get_network();
		self::assertNotNull( $network, 'A multisite environment must expose a current network.' );

		$blog_id = \wpmu_create_blog( $network->domain, '/uninstall-test-' . \uniqid() . '/', 'Uninstall Test', 1 );
		self::assertIsInt( $blog_id, 'The test fixture must create a second site.' );

		// Seed the full option footprint on both the current site and the new one.
		$this->seed_site_footprint();
		\switch_to_blog( $blog_id );
		$this->seed_site_footprint();
		\restore_current_blog();

		Plugin::get_instance()->get_installer()->uninstall();

		// Read both sites before asserting, so neither assertion narrows the repeated get_option() expression
		// and masks the other site's read.
		$current_site = $this->read_site_footprint();
		\switch_to_blog( $blog_id );
		$other_site = $this->read_site_footprint();
		\restore_current_blog();

		foreach ( self::OPTION_KEYS as $key ) {
			self::assertFalse( $current_site[ $key ], "The current site must have the $key option cleared." );
			self::assertFalse( $other_site[ $key ], "Every other network site must have the $key option cleared." );
		}

		\wp_delete_site( $blog_id );
	}

	protected function seed_site_footprint(): void {
		\update_option(
			'dws_plugin_template',
			array(
				'version'      => '2.0.0',
				'installed_at' => 123,
			)
		);
		\update_option(
			'dws_plugin_template_notices',
			array(
				'n' => array(
					'id'      => 'n',
					'message' => 'x',
				),
			)
		);
		\update_option( 'dws_plugin_template_enable_feature', 'yes' );
		\update_option( 'dws_plugin_template_greeting', 'Welcome!' );
		\update_option( 'dws_plugin_template-general', array( 'welcome_text' => 'Hi' ) );
	}

	/** @return array<string, mixed> */
	protected function read_site_footprint(): array {
		$state = array();
		foreach ( self::OPTION_KEYS as $key ) {
			$state[ $key ] = \get_option( $key );
		}

		return $state;
	}
}
