<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration\Greeting;

use DeepWebSolutions\PluginTemplate\Greeting\AccountDashboardGreeting;
use DeepWebSolutions\PluginTemplate\Options;
use DeepWebSolutions\PluginTemplate\Plugin;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Asset\Assets;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\PluginIdentity;
use DeepWebSolutions\PluginTemplate\Tests\Support\OrderStorageTrait;
use DeepWebSolutions\PluginTemplate\Tests\Support\RestoresOptionsTrait;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class AccountDashboardGreetingTest extends TestCase {
	use OrderStorageTrait;
	use RestoresOptionsTrait;

	protected const array OPTIONS = array( Options::GREETING_ENABLED, Options::GREETING, Options::GREETING_ORDERS );

	protected const string TRANSLATION = 'Translated greeting';

	protected int $customer = 0;

	#[\Override]
	public static function setUpBeforeClass(): void {
		require_once ABSPATH . 'wp-admin/includes/user.php';
	}

	public static function untouched_greetings(): array {
		return array(
			'unsaved'      => array( null ),
			'empty'        => array( '' ),
			'not-a-string' => array( array( 'Hello' ) ),
		);
	}

	public static function decisions(): array {
		return array(
			'switched-off'                    => array( 'no', 1, null, false ),
			'below-the-set-number'            => array( 'yes', 0, null, false ),
			'at-the-set-number'               => array( 'yes', 1, null, true ),
			'held-back-by-a-listener'         => array( 'yes', 1, false, false ),
			'shown-by-a-listener'             => array( 'yes', 0, true, true ),
			'switched-off-despite-a-listener' => array( 'no', 1, true, false ),
			'a-listener-returning-a-non-bool' => array( 'yes', 1, 1, false ),
		);
	}

	public static function unpaid_statuses(): array {
		return array(
			'pending'        => array( 'pending' ),
			'failed'         => array( 'failed' ),
			'cancelled'      => array( 'cancelled' ),
			'checkout-draft' => array( 'checkout-draft' ),
		);
	}

	#[\Override]
	protected function setUp(): void {
		$customer = wp_insert_user(
			array(
				'user_login' => Plugin::PREFIX . '_customer_' . wp_generate_password( 8, false ),
				'user_pass'  => wp_generate_password(),
				'role'       => 'customer',
			)
		);
		self::assertIsInt( $customer );
		$this->customer = $customer;
		wp_set_current_user( $customer );
		update_option( Options::GREETING_ENABLED, 'yes' );
	}

	#[After]
	protected function clean_up(): void {
		wp_set_current_user( 0 );
		wp_delete_user( $this->customer );
		wp_dequeue_style( 'dws-plugin-template-greeting' );
		wp_deregister_style( 'dws-plugin-template-greeting' );
	}

	public function test_the_greeting_hooks_the_account_dashboard_and_its_style(): void {
		$detail = new AccountDashboardGreeting( self::make_assets() );
		$detail->register_hooks();

		self::assertSame( 10, has_action( 'woocommerce_account_dashboard', array( $detail, 'print_greeting' ) ) );
		self::assertSame( 10, has_action( 'wp_enqueue_scripts', array( $detail, 'enqueue_style' ) ) );
		remove_action( 'woocommerce_account_dashboard', array( $detail, 'print_greeting' ) );
		remove_action( 'wp_enqueue_scripts', array( $detail, 'enqueue_style' ) );
	}

	#[DataProvider( 'decisions' )]
	public function test_the_decision_function_and_the_greeting_give_one_answer( string $enabled, int $paid, mixed $listener, bool $expected ): void {
		update_option( Options::GREETING_ENABLED, $enabled );
		update_option( Options::GREETING_ORDERS, '1' );
		$this->place_orders( $paid );
		$decide = static fn (): mixed => $listener;
		if ( ! \is_null( $listener ) ) {
			add_filter( 'dws_plugin_template/greeting/should_show', $decide );
		}

		try {
			self::assertSame( $expected, dws_tpl_should_show_greeting( $this->customer ) );
			self::assertSame( $expected, '' !== $this->print() );
		} finally {
			remove_filter( 'dws_plugin_template/greeting/should_show', $decide );
		}
	}

	public function test_a_listener_receives_whether_the_paid_orders_reach_the_set_number_and_the_customer(): void {
		update_option( Options::GREETING_ORDERS, '2' );
		$this->place_orders( 1 );
		$received = array();
		$record   = static function ( bool $shown, \WP_User $customer ) use ( &$received ): bool {
			$received[] = array( $shown, $customer->ID );
			return $shown;
		};
		add_filter( 'dws_plugin_template/greeting/should_show', $record, 10, 2 );

		try {
			dws_tpl_should_show_greeting( $this->customer );
		} finally {
			remove_filter( 'dws_plugin_template/greeting/should_show', $record );
		}

		self::assertSame( array( array( false, $this->customer ) ), $received );
	}

	public function test_a_user_id_that_names_no_user_is_never_greeted(): void {
		update_option( Options::GREETING_ORDERS, '1' );
		$this->place_orders( 1 );
		$this->create_order( array( 'status' => 'completed' ) );
		wp_delete_user( $this->customer );
		$asked  = 0;
		$listen = static function () use ( &$asked ): bool {
			++$asked;
			return true;
		};
		add_filter( 'dws_plugin_template/greeting/should_show', $listen );

		try {
			self::assertFalse( dws_tpl_should_show_greeting( $this->customer ) );
			self::assertFalse( dws_tpl_should_show_greeting( 0 ) );
		} finally {
			remove_filter( 'dws_plugin_template/greeting/should_show', $listen );
		}

		self::assertSame( 0, $asked );
	}

	public function test_the_greeting_style_loads_on_the_account_dashboard_only(): void {
		new AccountDashboardGreeting( self::make_assets() )->enqueue_style();
		self::assertFalse( wp_style_is( 'dws-plugin-template-greeting' ) );

		add_filter( 'woocommerce_is_account_page', '__return_true' );
		try {
			new AccountDashboardGreeting( self::make_assets() )->enqueue_style();
		} finally {
			remove_filter( 'woocommerce_is_account_page', '__return_true' );
		}

		self::assertTrue( wp_style_is( 'dws-plugin-template-greeting' ) );
		self::assertSame( plugins_url( 'assets/build/greeting.css', DWS_TPL_FILE ), wp_styles()->registered['dws-plugin-template-greeting']->src );
	}

	public function test_the_greeting_style_skips_the_account_page_endpoints(): void {
		global $wp;

		add_filter( 'woocommerce_is_account_page', '__return_true' );
		$wp->query_vars['orders'] = '';
		try {
			new AccountDashboardGreeting( self::make_assets() )->enqueue_style();
		} finally {
			unset( $wp->query_vars['orders'] );
			remove_filter( 'woocommerce_is_account_page', '__return_true' );
		}

		self::assertFalse( wp_style_is( 'dws-plugin-template-greeting' ) );
	}

	public function test_the_greeting_style_skips_logged_out_visitors(): void {
		wp_set_current_user( 0 );
		add_filter( 'woocommerce_is_account_page', '__return_true' );
		try {
			new AccountDashboardGreeting( self::make_assets() )->enqueue_style();
		} finally {
			remove_filter( 'woocommerce_is_account_page', '__return_true' );
		}

		self::assertFalse( wp_style_is( 'dws-plugin-template-greeting' ) );
	}

	public function test_the_greeting_style_enqueues_only_a_stylesheet_it_registered(): void {
		wp_register_style( 'dws-plugin-template-greeting', 'https://example.org/other.css', array(), '1' );
		add_filter( 'woocommerce_is_account_page', '__return_true' );
		try {
			new AccountDashboardGreeting( self::make_assets() )->enqueue_style();
		} finally {
			remove_filter( 'woocommerce_is_account_page', '__return_true' );
		}

		self::assertFalse( wp_style_is( 'dws-plugin-template-greeting' ) );
	}

	public function test_a_customer_whose_orders_reach_the_set_number_is_greeted_as_text(): void {
		update_option( Options::GREETING, 'Hi & <b>welcome</b>' );
		update_option( Options::GREETING_ORDERS, '2' );
		$this->place_orders( 2 );

		$notice = \Dom\HTMLDocument::createFromString( '<!DOCTYPE html><html><body>' . $this->print() . '</body></html>', \LIBXML_NOERROR )->querySelector( '[role="status"]' );

		self::assertNotNull( $notice );
		self::assertSame( 'greeting', $notice->getAttribute( 'data-dws-plugin-template' ) );
		self::assertSame( 'Hi & <b>welcome</b>', \trim( (string) $notice->textContent ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- A DOM property.
	}

	public function test_a_customer_below_the_set_number_is_not_greeted(): void {
		update_option( Options::GREETING_ORDERS, '2' );
		$this->place_orders( 1 );

		self::assertSame( '', $this->print() );
	}

	#[DataProvider( 'storages' )]
	public function test_paid_orders_count_towards_the_set_number_on_every_order_storage( bool $hpos, bool $sync ): void {
		$this->use_storage( $hpos, $sync );
		update_option( Options::GREETING_ORDERS, '2' );
		$this->place_orders( 1 );
		$this->place_orders( 1, 'failed' );

		self::assertSame( '', $this->print() );

		$this->place_orders( 1, 'processing' );

		self::assertNotSame( '', $this->print() );
	}

	#[DataProvider( 'unpaid_statuses' )]
	public function test_orders_that_are_not_paid_do_not_count_towards_the_set_number( string $status ): void {
		update_option( Options::GREETING_ORDERS, '2' );
		$this->place_orders( 1 );
		$this->place_orders( 2, $status );

		self::assertSame( '', $this->print() );
	}

	#[DataProvider( 'untouched_greetings' )]
	public function test_an_untouched_greeting_prints_its_translated_default( mixed $stored ): void {
		\is_null( $stored ) ? delete_option( Options::GREETING ) : update_option( Options::GREETING, $stored );
		update_option( Options::GREETING_ORDERS, '1' );
		$this->place_orders( 1 );
		$translate = static fn (): string => self::TRANSLATION;
		add_filter( 'gettext_' . Plugin::SLUG, $translate );

		try {
			self::assertStringContainsString( self::TRANSLATION, $this->print() );
		} finally {
			remove_filter( 'gettext_' . Plugin::SLUG, $translate );
		}
	}

	protected function place_orders( int $count, string $status = 'completed' ): void {
		for ( $placed = 0; $placed < $count; $placed++ ) {
			$this->create_order(
				array(
					'customer_id' => $this->customer,
					'status'      => $status,
				)
			);
		}
	}

	protected function print(): string {
		\ob_start();
		new AccountDashboardGreeting( self::make_assets() )->print_greeting();
		return (string) \ob_get_clean();
	}

	protected static function make_assets(): Assets {
		return new Assets( new PluginIdentity( DWS_TPL_FILE, Plugin::SLUG, Plugin::PREFIX ) );
	}
}
