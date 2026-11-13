<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration;

use DeepWebSolutions\PluginTemplate\Greeting\AccountDashboardGreeting;
use DeepWebSolutions\PluginTemplate\Options;
use DeepWebSolutions\PluginTemplate\Plugin;
use DeepWebSolutions\PluginTemplate\Settings;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\WooCommerce\Setting\SettingsTab;
use DeepWebSolutions\PluginTemplate\Tests\Support\RestoresOptionsTrait;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class SettingsTest extends TestCase {
	use RestoresOptionsTrait;

	protected const array OPTIONS = array( Options::GREETING_ENABLED, Options::GREETING, Options::GREETING_ORDERS, Options::DELETE_DATA );

	protected static SettingsTab $tab;

	#[\Override]
	public static function setUpBeforeClass(): void {
		require_once WC_ABSPATH . 'includes/admin/settings/class-wc-settings-page.php';
		require_once WC_ABSPATH . 'includes/admin/class-wc-admin-settings.php';

		// WooCommerce builds its settings pages once per request, so the class shares one build of the tab.
		foreach ( apply_filters( 'woocommerce_get_settings_pages', array() ) as $page ) {
			if ( $page instanceof SettingsTab && Plugin::PREFIX === $page->get_id() ) {
				self::$tab = $page;
			}
		}
	}

	public static function invalid_order_counts(): array {
		return array(
			'zero'     => array( '0' ),
			'negative' => array( '-2' ),
			'decimal'  => array( '2.5' ),
			'word'     => array( 'three' ),
			'empty'    => array( '' ),
			'too-big'  => array( '1e20' ),
		);
	}

	public static function field_texts(): array {
		return array(
			'toggle'    => array( Options::GREETING_ENABLED, 'Show the greeting', 'Greet returning customers' ),
			'message'   => array( Options::GREETING, 'Message', 'Leave empty to show the default greeting in each customer\'s language.' ),
			'threshold' => array( Options::GREETING_ORDERS, 'Orders before the greeting', 'Customers see the greeting once this many of their orders are paid.' ),
			'consent'   => array( Options::DELETE_DATA, 'Remove all data on uninstallation', 'Delete the plugin\'s data when it is uninstalled, so it must be configured again if it is installed later' ),
		);
	}

	public static function whole_order_counts(): array {
		return array(
			'padded'        => array( ' 3 ' ),
			'leading-zero'  => array( '03' ),
			'zero-decimals' => array( '3.0' ),
			'exponent'      => array( '0.3e1' ),
		);
	}

	public static function submitted_order_counts(): array {
		return array(
			'one'      => array( '1', '1' ),
			'padded'   => array( ' 3 ', '3' ),
			'zero'     => array( '0', null ),
			'negative' => array( '-3', null ),
			'decimal'  => array( '2.5', null ),
			'word'     => array( 'three', null ),
			'empty'    => array( '', null ),
			'array'    => array( array( '3' ), null ),
		);
	}

	#[After]
	protected function forget_the_rejections(): void {
		new \ReflectionProperty( \WC_Admin_Settings::class, 'errors' )->setValue( null, array() );
	}

	public function test_the_tab_shows_the_greeting_and_data_fields_with_their_defaults(): void {
		foreach ( self::OPTIONS as $key ) {
			delete_option( $key );
		}

		$form = $this->render();

		self::assertSame( 'DWS Plugin Template', self::$tab->get_label() );
		self::assertFalse( $form->getElementById( Options::GREETING_ENABLED )?->hasAttribute( 'checked' ) );
		self::assertSame( '', $form->getElementById( Options::GREETING )?->getAttribute( 'value' ) );
		self::assertSame( AccountDashboardGreeting::get_default_message(), $form->getElementById( Options::GREETING )?->getAttribute( 'placeholder' ) );
		self::assertSame( '1', $form->getElementById( Options::GREETING_ORDERS )?->getAttribute( 'value' ) );
		self::assertSame( '1', $form->getElementById( Options::GREETING_ORDERS )?->getAttribute( 'min' ) );
		self::assertFalse( $form->getElementById( Options::DELETE_DATA )?->hasAttribute( 'checked' ) );
	}

	#[DataProvider( 'field_texts' )]
	public function test_the_tab_words_each_field_by_what_it_does( string $key, string $title, string $description ): void {
		$row = (string) $this->render()->getElementById( $key )?->closest( 'tr' )?->textContent; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- A DOM property.

		self::assertStringContainsString( $title, $row );
		self::assertStringContainsString( $description, $row );
	}

	public function test_a_save_that_leaves_the_message_untouched_stores_no_translated_text(): void {
		delete_option( Options::GREETING );

		$this->save( array( Options::GREETING => $this->render()->getElementById( Options::GREETING )?->getAttribute( 'value' ) ) );

		self::assertSame( '', get_option( Options::GREETING ) );
	}

	public function test_the_uninstall_consent_round_trips_through_woocommerces_save(): void {
		$this->save( array( Options::DELETE_DATA => '1' ) );

		self::assertSame( 'yes', get_option( Options::DELETE_DATA ) );
		self::assertTrue( Options::get_bool( Options::DELETE_DATA ) );
		self::assertTrue( $this->render()->getElementById( Options::DELETE_DATA )?->hasAttribute( 'checked' ) );

		$this->save( array() );

		self::assertSame( 'no', get_option( Options::DELETE_DATA ) );
		self::assertFalse( Options::get_bool( Options::DELETE_DATA ) );
	}

	#[DataProvider( 'invalid_order_counts' )]
	public function test_an_order_count_that_is_not_a_whole_number_of_at_least_one_keeps_the_stored_count( string $posted ): void {
		update_option( Options::GREETING_ORDERS, '4' );

		$this->save( array( Options::GREETING_ORDERS => $posted ) );

		self::assertSame( '4', get_option( Options::GREETING_ORDERS ) );
		self::assertSame( array( 'Orders before the greeting was not saved and keeps its previous value. The other changes were saved. Enter a whole number of 1 or more, such as 3.' ), new \ReflectionProperty( \WC_Admin_Settings::class, 'errors' )->getValue() );
	}

	#[DataProvider( 'whole_order_counts' )]
	public function test_a_whole_order_count_of_at_least_one_is_stored_as_its_digits( string $posted ): void {
		$this->save( array( Options::GREETING_ORDERS => $posted ) );

		self::assertSame( '3', get_option( Options::GREETING_ORDERS ) );
		self::assertSame( 3, Options::get_int( Options::GREETING_ORDERS ) );
	}

	#[DataProvider( 'submitted_order_counts' )]
	public function test_an_order_count_validates_as_a_whole_number_of_at_least_one( mixed $submitted, ?string $expected ): void {
		$field     = \array_find( new Settings()->get_section_fields( Plugin::PREFIX, '' ), static fn ( array $field ): bool => Options::GREETING_ORDERS === $field['id'] );
		$validated = \is_array( $field ) && \is_callable( $field['validate'] ?? null ) ? $field['validate']( $submitted ) : null;

		\is_null( $expected ) ? self::assertInstanceOf( \WP_Error::class, $validated ) : self::assertSame( $expected, $validated );
	}

	protected function render(): \Dom\HTMLDocument {
		\ob_start();
		\WC_Admin_Settings::output_fields( self::$tab->get_settings_for_section( '' ) );
		return \Dom\HTMLDocument::createFromString( '<!DOCTYPE html><html><body>' . \ob_get_clean() . '</body></html>', \LIBXML_NOERROR );
	}

	// WooCommerce's form always posts its save button, so a save that changes nothing still runs.
	protected function save( array $posted ): void {
		\WC_Admin_Settings::save_fields( self::$tab->get_settings_for_section( '' ), $posted + array( 'save' => 'Save changes' ) );
		wp_cache_flush();
	}
}
