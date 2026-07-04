<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration;

use DeepWebSolutions\PluginTemplate\Component\ExampleWPSettings;
use DeepWebSolutions\PluginTemplate\Plugin;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Settings\Backend\WordPressSettingsBackend;
use PHPUnit\Framework\TestCase;

final class ExampleWPSettingsTest extends TestCase {
	private const PAGE_SLUG      = 'dws_plugin_template';
	private const SECTION_OPTION = 'dws_plugin_template-general';

	protected function setUp(): void {
		parent::setUp();

		\wp_set_current_user( 1 );
		\delete_option( self::SECTION_OPTION );
	}

	protected function tearDown(): void {
		\delete_option( self::SECTION_OPTION );

		parent::tearDown();
	}

	public function test_the_options_page_registers_under_the_settings_menu(): void {
		$this->register_example_settings();
		$backend = $this->backend();

		// add_submenu_page() lives in the admin bootstrap, which a CLI test process does not load.
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$backend->add_menu();

		global $submenu;
		$slugs = \array_column( $submenu['options-general.php'] ?? array(), 2 );
		self::assertContains(
			self::PAGE_SLUG,
			$slugs,
			'The example options page must register as a submenu of Settings.'
		);
	}

	public function test_the_section_setting_registers_with_the_settings_api(): void {
		$this->register_example_settings();

		// Drive the real admin_init/rest_api_init hook target directly, without firing the global hooks.
		$this->backend()->register_settings();

		self::assertArrayHasKey(
			self::SECTION_OPTION,
			\get_registered_settings(),
			'The section must register one grouped option with the Settings API.'
		);
	}

	public function test_a_field_round_trips_through_the_grouped_section_option(): void {
		$this->register_example_settings();
		$backend = $this->backend();

		$backend->set( 'welcome_text', 'Hello from the test' );

		self::assertSame( 'Hello from the test', $backend->get( 'welcome_text' ) );
		self::assertTrue( $backend->has( 'welcome_text' ) );

		// The backend persists the whole section as one grouped wp_options row, keyed by field id.
		$row = \get_option( self::SECTION_OPTION );
		self::assertIsArray( $row );
		self::assertSame( 'Hello from the test', $row['welcome_text'] ?? null );

		self::assertTrue( $backend->delete( 'welcome_text' ) );
		self::assertFalse( $backend->has( 'welcome_text' ) );
	}

	public function test_the_descriptor_defaults_apply_while_nothing_is_stored(): void {
		$this->register_example_settings();
		$backend = $this->backend();

		self::assertFalse( $backend->has( 'items_per_page' ) );
		self::assertSame( 10, $backend->get( 'items_per_page', 10 ) );
	}

	private function register_example_settings(): void {
		$settings = Plugin::get_instance()->get_container()->get( ExampleWPSettings::class );
		self::assertInstanceOf( ExampleWPSettings::class, $settings );

		// Drive the real init-hook target directly: the descriptor is built and registered on the
		// container-wired backend, exactly as the booted plugin does on `init`, without depending on hook order.
		$settings->register_settings_page();
	}

	private function backend(): WordPressSettingsBackend {
		$backend = Plugin::get_instance()->get_container()->get( WordPressSettingsBackend::class );
		self::assertInstanceOf( WordPressSettingsBackend::class, $backend );

		return $backend;
	}
}
