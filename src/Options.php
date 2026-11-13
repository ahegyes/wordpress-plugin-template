<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate;

\defined( 'ABSPATH' ) || exit;

/**
 * Names the plugin's stored settings and their defaults, and reads each setting as its type, guarding the stored value.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class Options {
	// region FIELDS AND CONSTANTS

	/**
	 * The option that holds 'yes' where the site consents to uninstall removing the plugin's data.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     string
	 */
	public const string DELETE_DATA = Plugin::PREFIX . '_delete_data';

	/**
	 * The option that holds the greeting.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     string
	 */
	public const string GREETING = Plugin::PREFIX . '_greeting';

	/**
	 * The option that holds 'yes' where the site greets returning customers on their account dashboard.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     string
	 */
	public const string GREETING_ENABLED = Plugin::PREFIX . '_greeting_enabled';

	/**
	 * The option that holds the number of orders a customer places before the greeting shows.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     string
	 */
	public const string GREETING_ORDERS = Plugin::PREFIX . '_greeting_orders';

	/**
	 * The value each option reads as while nothing valid is stored.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     array<string, bool|int|string>
	 */
	public const array DEFAULTS = array(
		self::DELETE_DATA      => false,
		self::GREETING_ENABLED => false,
		self::GREETING_ORDERS  => 1,
	);

	// endregion

	// region MAGIC METHODS

	/**
	 * Constructor.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	protected function __construct() {}

	// endregion

	// region METHODS

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
	public static function get_bool( string $key ): bool {
		$default = self::get_default( $key );
		return \filter_var( get_option( $key, $default ), \FILTER_VALIDATE_BOOL, \FILTER_NULL_ON_FAILURE ) ?? ( true === $default );
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
	public static function get_int( string $key ): int {
		$default = self::get_default( $key );
		return \filter_var( get_option( $key, $default ), \FILTER_VALIDATE_INT, \FILTER_NULL_ON_FAILURE ) ?? ( \is_int( $default ) ? $default : 0 );
	}

	/**
	 * Returns an option as a string, or its default, or an empty string, where no string is stored.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   string $key The option's key.
	 *
	 * @return  string
	 */
	public static function get_string( string $key ): string {
		$default = self::get_default( $key );
		$stored  = get_option( $key, $default );
		return \is_string( $stored ) ? $stored : ( \is_string( $default ) ? $default : '' );
	}

	// endregion

	// region HELPERS

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
	protected static function get_default( string $key ): mixed {
		return self::DEFAULTS[ $key ] ?? null;
	}

	// endregion
}
