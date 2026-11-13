<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate;

\defined( 'ABSPATH' ) || exit;

/**
 * Holds the plugin's migration steps, which bring its stored data to the revision its code expects.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class Migrations {
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
	 * Returns the migration steps, append-only, where index 0 brings the data to revision 1.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  list<callable(): void>
	 */
	public static function get_steps(): array {
		return array(
			self::add_settings( ... ),
		);
	}

	// endregion

	// region HELPERS

	/**
	 * Stores the defaults of the settings that need no translation.
	 *
	 * A translated default is never stored, because it would keep the language the site had when the step ran.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @throws  \RuntimeException Thrown when a default could not be stored.
	 *
	 * @return  void
	 */
	protected static function add_settings(): void {
		$defaults = array(
			Options::GREETING_ENABLED => wc_bool_to_string( Options::DEFAULTS[ Options::GREETING_ENABLED ] ),
			Options::GREETING_ORDERS  => (string) Options::DEFAULTS[ Options::GREETING_ORDERS ],
			Options::DELETE_DATA      => wc_bool_to_string( Options::DEFAULTS[ Options::DELETE_DATA ] ),
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) && ! add_option( $key, $value, '', true ) ) {
				throw new \RuntimeException( "The option $key could not be stored." ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Developer-facing, never rendered.
			}
		}
	}

	// endregion
}
