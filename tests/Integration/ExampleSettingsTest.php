<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration;

use DeepWebSolutions\PluginTemplate\Component\ExampleSettings;
use DeepWebSolutions\PluginTemplate\Plugin;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\WooCommerce\Backend\WooCommerceSettingsBackend;
use DeepWebSolutions\PluginTemplate\Settings\ExampleWCSettingsPage;
use PHPUnit\Framework\TestCase;

final class ExampleSettingsTest extends TestCase {
	private const TAB_ID      = 'dws_plugin_template';
	private const OPTION_KEYS = array( 'dws_plugin_template_enable_feature', 'dws_plugin_template_greeting' );

	/**
	 * @var array<string, mixed>
	 */
	private array $saved_hooks = array();

	protected function setUp(): void {
		parent::setUp();

		// The page subclass extends \WC_Settings_Page, which WooCommerce loads only when it builds its settings
		// pages; the backend defers instantiating the subclass into the get_settings_pages filter for that
		// reason, so the parent must be loadable before the filter is applied here.
		if ( ! \class_exists( 'WC_Settings_Page' ) ) {
			require_once WP_PLUGIN_DIR . '/woocommerce/includes/admin/settings/class-wc-settings-page.php';
		}

		\wp_set_current_user( 1 );

		// Isolate the hooks the example settings path registers on, capturing each so tearDown restores it: the
		// plugin's own boot-time registration (and any sibling test's) survives instead of being wiped.
		// woocommerce_get_settings_pages is global to every settings page in the process.
		global $wp_filter;
		foreach ( $this->isolated_hooks() as $hook ) {
			$this->saved_hooks[ $hook ] = $wp_filter[ $hook ] ?? null;
			unset( $wp_filter[ $hook ] );
		}

		$this->delete_settings_options();
	}

	protected function tearDown(): void {
		$this->delete_settings_options();

		global $wp_filter;
		foreach ( $this->saved_hooks as $hook => $saved ) {
			if ( null !== $saved ) {
				$wp_filter[ $hook ] = $saved;
			} else {
				unset( $wp_filter[ $hook ] );
			}
		}

		parent::tearDown();
	}

	public function test_the_example_settings_path_registers_its_tab_in_woocommerce(): void {
		$this->register_example_settings();

		$page = $this->example_page( \apply_filters( 'woocommerce_get_settings_pages', array() ) );

		self::assertInstanceOf( ExampleWCSettingsPage::class, $page );
		self::assertSame( self::TAB_ID, (string) $page->get_id() );
	}

	public function test_the_registered_tab_exposes_the_descriptor_fields(): void {
		$this->register_example_settings();

		// Assert against the page object WooCommerce actually collected through the filter, not a fresh
		// instance: the descriptor lives in a class-static map, so a fresh instance could read a binding
		// left by an earlier test rather than this registration.
		$page = $this->example_page( \apply_filters( 'woocommerce_get_settings_pages', array() ) );
		self::assertInstanceOf( ExampleWCSettingsPage::class, $page );

		$ids = \array_column( $page->get_settings_for_section( '' ), 'id' );

		self::assertContains( 'dws_plugin_template_enable_feature', $ids );
		self::assertContains( 'dws_plugin_template_greeting', $ids );
	}

	public function test_a_descriptor_field_value_round_trips_through_its_prefixed_option(): void {
		$this->register_example_settings();
		$backend = $this->backend();

		$backend->set( 'greeting', 'Welcome!' );

		self::assertSame( 'Welcome!', $backend->get( 'greeting' ) );
		self::assertSame( 'Welcome!', \get_option( 'dws_plugin_template_greeting' ) );
		self::assertTrue( $backend->has( 'greeting' ) );
		self::assertTrue( $backend->delete( 'greeting' ) );
		self::assertFalse( $backend->has( 'greeting' ) );
	}

	public function test_the_checkbox_field_round_trips_its_yes_no_value(): void {
		$this->register_example_settings();
		$backend = $this->backend();

		// WooCommerce persists a checkbox as the 'no'/'yes' strings; the backend round-trips them verbatim.
		$backend->set( 'enable_feature', 'no' );
		self::assertSame( 'no', $backend->get( 'enable_feature' ) );
		self::assertSame( 'no', \get_option( 'dws_plugin_template_enable_feature' ) );

		$backend->set( 'enable_feature', 'yes' );
		self::assertSame( 'yes', $backend->get( 'enable_feature' ) );
		self::assertTrue( $backend->has( 'enable_feature' ) );
	}

	private function register_example_settings(): void {
		$settings = Plugin::get_instance()->get_container()->get( ExampleSettings::class );
		self::assertInstanceOf( ExampleSettings::class, $settings );

		// Drive the real init-hook target directly: the descriptor is built and registered on the
		// container-wired backend, exactly as the booted plugin does on `init`, without depending on hook order.
		$settings->register_settings_page();
	}

	private function backend(): WooCommerceSettingsBackend {
		$backend = Plugin::get_instance()->get_container()->get( WooCommerceSettingsBackend::class );
		self::assertInstanceOf( WooCommerceSettingsBackend::class, $backend );

		return $backend;
	}

	private function example_page( mixed $pages ): ?ExampleWCSettingsPage {
		if ( ! \is_array( $pages ) ) {
			return null;
		}

		foreach ( $pages as $page ) {
			if ( $page instanceof ExampleWCSettingsPage ) {
				return $page;
			}
		}

		return null;
	}

	/** @return list<string> */
	private function isolated_hooks(): array {
		$hooks = array( 'woocommerce_get_settings_pages' );

		foreach ( self::OPTION_KEYS as $key ) {
			$hooks[] = 'woocommerce_admin_settings_sanitize_option_' . $key;
		}

		return $hooks;
	}

	private function delete_settings_options(): void {
		foreach ( self::OPTION_KEYS as $key ) {
			\delete_option( $key );
		}
	}
}
