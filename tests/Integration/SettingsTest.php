<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration;

use DeepWebSolutions\PluginTemplate\Plugin;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\WooCommerce\Setting\SettingsTab;
use DeepWebSolutions\PluginTemplate\Tests\Support\RestoresOptionsTrait;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class SettingsTest extends TestCase {
	use RestoresOptionsTrait;

	protected const array OPTIONS = array( DWS_TPL_OPTION_GREETING_ENABLED, DWS_TPL_OPTION_GREETING, DWS_TPL_OPTION_GREETING_ORDERS, DWS_TPL_OPTION_DELETE_DATA );

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
			'toggle'    => array( DWS_TPL_OPTION_GREETING_ENABLED, 'Show the greeting', 'Greet returning customers' ),
			'message'   => array( DWS_TPL_OPTION_GREETING, 'Message', 'Leave empty to show the default greeting in each customer\'s language.' ),
			'threshold' => array( DWS_TPL_OPTION_GREETING_ORDERS, 'Orders before the greeting', 'Customers see the greeting once this many of their orders are paid.' ),
			'consent'   => array( DWS_TPL_OPTION_DELETE_DATA, 'Remove all data on uninstallation', 'Delete the plugin\'s data when it is uninstalled, so it must be configured again if it is installed later' ),
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

	#[After]
	protected function forget_the_rejections(): void {
		( new \ReflectionProperty( \WC_Admin_Settings::class, 'errors' ) )->setValue( null, array() );
	}

	public function test_the_tab_shows_the_greeting_and_data_fields_with_their_defaults(): void {
		foreach ( self::OPTIONS as $key ) {
			delete_option( $key );
		}

		$form = $this->render();

		self::assertSame( 'DWS Plugin Template', self::$tab->get_label() );
		self::assertFalse( $form->getElementById( DWS_TPL_OPTION_GREETING_ENABLED )?->hasAttribute( 'checked' ) );
		self::assertSame( '', $form->getElementById( DWS_TPL_OPTION_GREETING )?->getAttribute( 'value' ) );
		self::assertSame( dws_tpl_get_option_text( DWS_TPL_OPTION_GREETING ), $form->getElementById( DWS_TPL_OPTION_GREETING )?->getAttribute( 'placeholder' ) );
		self::assertSame( '1', $form->getElementById( DWS_TPL_OPTION_GREETING_ORDERS )?->getAttribute( 'value' ) );
		self::assertSame( '1', $form->getElementById( DWS_TPL_OPTION_GREETING_ORDERS )?->getAttribute( 'min' ) );
		self::assertFalse( $form->getElementById( DWS_TPL_OPTION_DELETE_DATA )?->hasAttribute( 'checked' ) );
	}

	#[DataProvider( 'field_texts' )]
	public function test_the_tab_words_each_field_by_what_it_does( string $key, string $title, string $description ): void {
		$row = (string) $this->render()->getElementById( $key )?->closest( 'tr' )?->textContent; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- A DOM property.

		self::assertStringContainsString( $title, $row );
		self::assertStringContainsString( $description, $row );
	}

	public function test_a_save_that_leaves_the_message_untouched_stores_no_translated_text(): void {
		delete_option( DWS_TPL_OPTION_GREETING );

		$this->save( array( DWS_TPL_OPTION_GREETING => $this->render()->getElementById( DWS_TPL_OPTION_GREETING )?->getAttribute( 'value' ) ) );

		self::assertSame( '', get_option( DWS_TPL_OPTION_GREETING ) );
		self::assertSame( dws_tpl_get_option_text( DWS_TPL_OPTION_GREETING ), dws_tpl_get_string_option( DWS_TPL_OPTION_GREETING ) );
	}

	public function test_the_retention_opt_in_round_trips_through_woocommerces_save(): void {
		$this->save( array( DWS_TPL_OPTION_DELETE_DATA => '1' ) );

		self::assertSame( 'yes', get_option( DWS_TPL_OPTION_DELETE_DATA ) );
		self::assertTrue( dws_tpl_get_bool_option( DWS_TPL_OPTION_DELETE_DATA ) );
		self::assertTrue( $this->render()->getElementById( DWS_TPL_OPTION_DELETE_DATA )?->hasAttribute( 'checked' ) );

		$this->save( array() );

		self::assertSame( 'no', get_option( DWS_TPL_OPTION_DELETE_DATA ) );
		self::assertFalse( dws_tpl_get_bool_option( DWS_TPL_OPTION_DELETE_DATA ) );
	}

	#[DataProvider( 'invalid_order_counts' )]
	public function test_an_order_count_that_is_not_a_whole_number_of_at_least_one_keeps_the_stored_count( string $posted ): void {
		update_option( DWS_TPL_OPTION_GREETING_ORDERS, '4' );

		$this->save( array( DWS_TPL_OPTION_GREETING_ORDERS => $posted ) );

		self::assertSame( '4', get_option( DWS_TPL_OPTION_GREETING_ORDERS ) );
		self::assertSame( array( 'Orders before the greeting was not saved and keeps its previous value. The other changes were saved. Enter a whole number of 1 or more, such as 3.' ), ( new \ReflectionProperty( \WC_Admin_Settings::class, 'errors' ) )->getValue() );
	}

	#[DataProvider( 'whole_order_counts' )]
	public function test_a_whole_order_count_of_at_least_one_is_stored_as_its_digits( string $posted ): void {
		$this->save( array( DWS_TPL_OPTION_GREETING_ORDERS => $posted ) );

		self::assertSame( '3', get_option( DWS_TPL_OPTION_GREETING_ORDERS ) );
		self::assertSame( 3, dws_tpl_get_int_option( DWS_TPL_OPTION_GREETING_ORDERS ) );
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
