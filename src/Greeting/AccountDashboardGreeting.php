<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Greeting;

use DeepWebSolutions\PluginTemplate\Options;
use DeepWebSolutions\PluginTemplate\Plugin;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\ComponentInterface;

\defined( 'ABSPATH' ) || exit;

/**
 * Greets customers on their account dashboard once the set number of their orders are paid.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class AccountDashboardGreeting implements ComponentInterface {
	// region INHERITED METHODS

	/**
	 * {@inheritDoc}
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	#[\Override]
	public function register_hooks(): void {
		add_action( 'woocommerce_account_dashboard', array( $this, 'print_greeting' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_style' ) );
	}

	// endregion

	// region METHODS

	/**
	 * Returns the greeting's default message in the visitor's language, which shows wherever the stored message is empty.
	 *
	 * The default is never stored, so that saving the settings does not fix one language for every customer. Callers read it from init on, because WordPress flags translations loaded earlier.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  string
	 */
	public static function get_default_message(): string {
		return __( 'Welcome back, and thank you for shopping with us again.', 'dws-plugin-template' );
	}

	// endregion

	// region HOOKS

	/**
	 * Prints the greeting as a WooCommerce notice to a customer whose paid orders reach the set number.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function print_greeting(): void {
		$threshold = Options::get_int( Options::GREETING_ORDERS );
		$paid      = wc_get_orders(
			array(
				'customer_id' => get_current_user_id(),
				'status'      => wc_get_is_paid_statuses(),
				'limit'       => $threshold,
				'return'      => 'ids',
			)
		);
		if ( ! \is_array( $paid ) || \count( $paid ) < $threshold ) {
			return;
		}

		$greeting = Options::get_string( Options::GREETING );
		wc_print_notice( esc_html( '' === $greeting ? self::get_default_message() : $greeting ), 'notice', array( Plugin::SLUG => 'greeting' ) );
	}

	/**
	 * Enqueues the greeting's stylesheet on a logged-in customer's account dashboard.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function enqueue_style(): void {
		if ( ! is_user_logged_in() || ! is_account_page() || is_wc_endpoint_url() ) {
			return;
		}

		if ( dws_tpl_register_style( Plugin::SLUG . '-greeting', 'assets/build/greeting.css' ) ) {
			wp_enqueue_style( Plugin::SLUG . '-greeting' );
		}
	}

	// endregion
}
