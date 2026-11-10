<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration\Greeting;

use DeepWebSolutions\PluginTemplate\Greeting\AccountDashboardGreeting;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class AccountDashboardGreetingTest extends TestCase {
	protected const array KEYS = array( DWS_TPL_OPTION_GREETING, DWS_TPL_OPTION_GREETING_ORDERS );

	protected array $stored = array();

	protected int $customer = 0;

	protected array $orders = array();

	#[\Override]
	public static function setUpBeforeClass(): void {
		require_once ABSPATH . 'wp-admin/includes/user.php';
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
		foreach ( self::KEYS as $key ) {
			$this->stored[ $key ] = get_option( $key );
		}

		$customer = wp_insert_user(
			array(
				'user_login' => 'dws_tpl_customer_' . \wp_generate_password( 8, false ),
				'user_pass'  => wp_generate_password(),
				'role'       => 'customer',
			)
		);
		self::assertIsInt( $customer );
		$this->customer = $customer;
		wp_set_current_user( $customer );
	}

	#[After]
	protected function clean_up(): void {
		foreach ( $this->stored as $key => $value ) {
			false === $value ? delete_option( $key ) : update_option( $key, $value );
		}

		foreach ( $this->orders as $order ) {
			$order->delete( true );
		}

		wp_set_current_user( 0 );
		wp_delete_user( $this->customer );
		wp_dequeue_style( 'dws-plugin-template-greeting' );
		wp_deregister_style( 'dws-plugin-template-greeting' );
	}

	public function test_the_greeting_prints_on_the_account_dashboard(): void {
		$detail = new AccountDashboardGreeting();
		$detail->register_hooks();

		self::assertSame( 10, has_action( 'woocommerce_account_dashboard', array( $detail, 'print_greeting' ) ) );
		self::assertSame( 10, has_action( 'wp_enqueue_scripts', array( $detail, 'enqueue_style' ) ) );
		remove_action( 'woocommerce_account_dashboard', array( $detail, 'print_greeting' ) );
		remove_action( 'wp_enqueue_scripts', array( $detail, 'enqueue_style' ) );
	}

	public function test_the_greeting_style_loads_on_the_account_dashboard_only(): void {
		( new AccountDashboardGreeting() )->enqueue_style();
		self::assertFalse( wp_style_is( 'dws-plugin-template-greeting' ) );

		add_filter( 'woocommerce_is_account_page', '__return_true' );
		try {
			( new AccountDashboardGreeting() )->enqueue_style();
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
			new AccountDashboardGreeting()->enqueue_style();
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
			new AccountDashboardGreeting()->enqueue_style();
		} finally {
			remove_filter( 'woocommerce_is_account_page', '__return_true' );
		}

		self::assertFalse( wp_style_is( 'dws-plugin-template-greeting' ) );
	}

	public function test_the_greeting_style_enqueues_only_a_stylesheet_it_registered(): void {
		wp_register_style( 'dws-plugin-template-greeting', 'https://example.org/other.css', array(), '1' );
		add_filter( 'woocommerce_is_account_page', '__return_true' );
		try {
			new AccountDashboardGreeting()->enqueue_style();
		} finally {
			remove_filter( 'woocommerce_is_account_page', '__return_true' );
		}

		self::assertFalse( wp_style_is( 'dws-plugin-template-greeting' ) );
	}

	public function test_a_customer_whose_orders_reach_the_set_number_is_greeted_as_text(): void {
		update_option( DWS_TPL_OPTION_GREETING, 'Hi & <b>welcome</b>' );
		update_option( DWS_TPL_OPTION_GREETING_ORDERS, '2' );
		$this->place_orders( 2 );

		$notice = \Dom\HTMLDocument::createFromString( '<!DOCTYPE html><html><body>' . $this->print() . '</body></html>', \LIBXML_NOERROR )->querySelector( '[role="status"]' );

		self::assertNotNull( $notice );
		self::assertSame( 'greeting', $notice->getAttribute( 'data-dws-plugin-template' ) );
		self::assertSame( 'Hi & <b>welcome</b>', \trim( (string) $notice->textContent ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- A DOM property.
	}

	public function test_a_customer_below_the_set_number_is_not_greeted(): void {
		update_option( DWS_TPL_OPTION_GREETING_ORDERS, '2' );
		$this->place_orders( 1 );

		self::assertSame( '', $this->print() );
	}

	#[DataProvider( 'unpaid_statuses' )]
	public function test_orders_that_are_not_paid_do_not_count_towards_the_set_number( string $status ): void {
		update_option( DWS_TPL_OPTION_GREETING_ORDERS, '2' );
		$this->place_orders( 1 );
		$this->place_orders( 2, $status );

		self::assertSame( '', $this->print() );
	}

	public function test_an_unsaved_greeting_prints_its_translated_default(): void {
		delete_option( DWS_TPL_OPTION_GREETING );
		delete_option( DWS_TPL_OPTION_GREETING_ORDERS );
		$this->place_orders( 1 );

		self::assertStringContainsString( esc_html( dws_tpl_get_option_text( DWS_TPL_OPTION_GREETING ) ), $this->print() );
	}

	public function test_an_empty_greeting_prints_its_translated_default(): void {
		update_option( DWS_TPL_OPTION_GREETING, '' );
		$this->place_orders( 1 );

		self::assertStringContainsString( esc_html( dws_tpl_get_option_text( DWS_TPL_OPTION_GREETING ) ), $this->print() );
	}

	protected function place_orders( int $count, string $status = 'completed' ): void {
		for ( $placed = 0; $placed < $count; $placed++ ) {
			$this->orders[] = wc_create_order(
				array(
					'customer_id' => $this->customer,
					'status'      => $status,
				)
			);
		}
	}

	protected function print(): string {
		\ob_start();
		( new AccountDashboardGreeting() )->print_greeting();

		return (string) \ob_get_clean();
	}
}
