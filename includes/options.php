<?php declare( strict_types=1 );
/**
 * Holds the plugin's option keys and defaults, and reads each option as its type, guarding the stored value.
 *
 * @since   2.0.0
 * @version 2.0.0
 *
 * @package DeepWebSolutions\PluginTemplate
 */

use DeepWebSolutions\PluginTemplate\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * The option that holds 'yes' where the site consents to uninstall removing the plugin's data.
 *
 * @since   2.0.0
 * @version 2.0.0
 *
 * @var     string
 */
const DWS_TPL_OPTION_DELETE_DATA = Plugin::PREFIX . '_delete_data';

/**
 * The value each option reads as while nothing valid is stored.
 *
 * @since   2.0.0
 * @version 2.0.0
 *
 * @var     array<string, bool|int|string|list<string>>
 */
const DWS_TPL_OPTION_DEFAULTS = array(
	DWS_TPL_OPTION_DELETE_DATA => false,
);

/**
 * Returns an option as a boolean, which reads checkbox values such as 'yes' and 'no'.
 *
 * @since   2.0.0
 * @version 2.0.0
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
 * @since   2.0.0
 * @version 2.0.0
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
 * Returns an option as a string.
 *
 * @since   2.0.0
 * @version 2.0.0
 *
 * @param   string $key The option's key.
 *
 * @return  string
 */
function dws_tpl_get_string_option( string $key ): string {
	$default = dws_tpl_get_option_default( $key );
	$stored  = get_option( $key, $default );

	return is_string( $stored ) ? $stored : ( is_string( $default ) ? $default : '' );
}

/**
 * Returns an option as a list of strings, keeping the stored strings in order.
 *
 * @since   2.0.0
 * @version 2.0.0
 *
 * @param   string $key The option's key.
 *
 * @return  list<string>
 */
function dws_tpl_get_list_option( string $key ): array {
	$default = dws_tpl_get_option_default( $key );
	$stored  = get_option( $key, $default );
	$stored  = is_array( $stored ) ? $stored : $default;

	return is_array( $stored ) ? array_values( array_filter( $stored, 'is_string' ) ) : array();
}

/**
 * Returns the option's default, or null for an option without one.
 *
 * @since   2.0.0
 * @version 2.0.0
 *
 * @param   string $key The option's key.
 *
 * @return  mixed
 */
function dws_tpl_get_option_default( string $key ) {
	return DWS_TPL_OPTION_DEFAULTS[ $key ] ?? null;
}
