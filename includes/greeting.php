<?php declare( strict_types=1 );
/**
 * Defines the function through which another plugin asks whether a customer sees the account dashboard greeting.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @package DeepWebSolutions\PluginTemplate
 */

use DeepWebSolutions\PluginTemplate\Greeting\AccountDashboardGreeting;

defined( 'ABSPATH' ) || exit;

/**
 * Returns whether a customer sees the account dashboard greeting.
 *
 * It answers as the greeting does when it prints, while the greeting loads only where the site switched it on at boot, so a setting written later in the same request can make the two differ. It does not tell whether the plugin booted, which the dws_plugin_template/boot/succeeded action announces. Callers ask from wp_loaded on, where WooCommerce is active, since WooCommerce registers its order types and statuses on init.
 *
 * @api
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   int $user_id The customer's user id.
 *
 * @return  bool
 */
function dws_tpl_should_show_greeting( int $user_id ): bool {
	return AccountDashboardGreeting::should_show( $user_id );
}
