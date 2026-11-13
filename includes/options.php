<?php declare( strict_types=1 );
/**
 * Holds the plugin's option keys and defaults, and reads each option as its type, guarding the stored value.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @package DeepWebSolutions\PluginTemplate
 */

defined( 'ABSPATH' ) || exit;

/**
 * The option that holds 'yes' where the site consents to uninstall removing the plugin's data.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @var     string
 */
const DWS_TPL_OPTION_DELETE_DATA = 'dws_tpl_delete_data';

/**
 * The option that holds the greeting.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @var     string
 */
const DWS_TPL_OPTION_GREETING = 'dws_tpl_greeting';

/**
 * The option that holds 'yes' where the site greets returning customers on their account dashboard.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @var     string
 */
const DWS_TPL_OPTION_GREETING_ENABLED = 'dws_tpl_greeting_enabled';

/**
 * The option that holds the number of orders a customer places before the greeting shows.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @var     string
 */
const DWS_TPL_OPTION_GREETING_ORDERS = 'dws_tpl_greeting_orders';

/**
 * The value each option reads as while nothing valid is stored.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @var     array<string, bool|int|string>
 */
const DWS_TPL_OPTION_DEFAULTS = array(
	DWS_TPL_OPTION_DELETE_DATA      => false,
	DWS_TPL_OPTION_GREETING_ENABLED => false,
	DWS_TPL_OPTION_GREETING_ORDERS  => 1,
);

/**
 * Returns an option as a boolean, which reads checkbox values such as 'yes' and 'no'.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   string $key The option's key.
 *
 * @return  bool
 */
function dws_tpl_get_bool_option( string $key ): bool {
	$default = dws_tpl_get_option_default( $key );
	return filter_var( get_option( $key, $default ), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE ) ?? ( true === $default );
}

/**
 * Returns an option as an integer.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   string $key The option's key.
 *
 * @return  int
 */
function dws_tpl_get_int_option( string $key ): int {
	$default = dws_tpl_get_option_default( $key );
	return filter_var( get_option( $key, $default ), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE ) ?? ( is_int( $default ) ? $default : 0 );
}

/**
 * Returns an option as a string, reading a missing or empty value of an option with a translated default as that default.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   string $key The option's key.
 *
 * @return  string
 */
function dws_tpl_get_string_option( string $key ): string {
	$text    = dws_tpl_get_option_text( $key );
	$default = dws_tpl_get_option_default( $key ) ?? $text;
	$stored  = get_option( $key, $default );
	if ( '' === $stored ) {
		return $text; // The field shows a translated default as its placeholder, so an untouched field stores an empty value.
	}

	return is_string( $stored ) ? $stored : ( is_string( $default ) ? $default : '' );
}

/**
 * Returns an option's translated default, or an empty string for an option without one.
 *
 * Callers read it from init on, because WordPress flags translations loaded earlier.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   string $key The option's key.
 *
 * @return  string
 */
function dws_tpl_get_option_text( string $key ): string {
	switch ( $key ) {
		case DWS_TPL_OPTION_GREETING:
			return __( 'Welcome back, and thank you for shopping with us again.', 'dws-plugin-template' );
		default:
			return '';
	}
}

/**
 * Returns the number of orders before the greeting shows as the digits WooCommerce stores, or an error when the submission is not a whole number of at least 1.
 *
 * The number input sends a whole number in any form a browser accepts, such as 03 or 3.0, so the submission parses as a float.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   mixed $submitted The submitted value.
 *
 * @return  string|WP_Error
 */
function dws_tpl_validate_greeting_orders( $submitted ) {
	$orders = is_string( $submitted ) ? filter_var( $submitted, FILTER_VALIDATE_FLOAT ) : false;
	if ( false === $orders || $orders < 1 || $orders >= PHP_INT_MAX || floor( $orders ) !== $orders ) {
		return new WP_Error( 'invalid_number', __( 'Enter a whole number of 1 or more, such as 3.', 'dws-plugin-template' ) );
	}

	return (string) (int) $orders;
}

/**
 * Returns the option's default, or null for an option without one.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @param   string $key The option's key.
 *
 * @return  mixed
 */
function dws_tpl_get_option_default( string $key ) {
	return DWS_TPL_OPTION_DEFAULTS[ $key ] ?? null;
}
