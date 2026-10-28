<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate;

\defined( 'ABSPATH' ) || exit;

/**
 * Holds the plugin's option keys and defaults, and reads each option as its type, guarding the stored value.
 *
 * @since   2.0.0
 * @version 2.0.0
 */
final class Options {
	// region FIELDS AND CONSTANTS

	/**
	 * The option that holds 'yes' where the site consents to uninstall removing the plugin's data.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @var     string
	 */
	public const string DELETE_DATA = Plugin::PREFIX . '_delete_data';

	/**
	 * The value each option reads as while nothing valid is stored.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @var     array<string, bool|int|string|list<string>>
	 */
	public const array DEFAULTS = array(
		self::DELETE_DATA => false,
	);

	// endregion

	// region MAGIC METHODS

	/**
	 * Constructor.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	protected function __construct() {}

	// endregion

	// region GETTERS

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
	public static function get_bool( string $key ): bool {
		$default = self::get_default( $key );

		return \filter_var( get_option( $key, $default ), \FILTER_VALIDATE_BOOL, \FILTER_NULL_ON_FAILURE ) ?? ( true === $default );
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
	public static function get_int( string $key ): int {
		$default = self::get_default( $key );

		return \filter_var( get_option( $key, $default ), \FILTER_VALIDATE_INT, \FILTER_NULL_ON_FAILURE ) ?? ( \is_int( $default ) ? $default : 0 );
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
	public static function get_string( string $key ): string {
		$default = self::get_default( $key );
		$stored  = get_option( $key, $default );

		return \is_string( $stored ) ? $stored : ( \is_string( $default ) ? $default : '' );
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
	public static function get_list( string $key ): array {
		$default = self::get_default( $key );
		$stored  = get_option( $key, $default );
		$stored  = \is_array( $stored ) ? $stored : $default;

		return \is_array( $stored ) ? \array_values( \array_filter( $stored, \is_string( ... ) ) ) : array();
	}

	// endregion

	// region HELPERS

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
	protected static function get_default( string $key ): mixed {
		return self::DEFAULTS[ $key ] ?? null;
	}

	// endregion
}
