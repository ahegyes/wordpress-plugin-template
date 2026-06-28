<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Unit;

/**
 * Records the WordPress calls the boot path makes, so the mock-WP Unit smoke can assert on them. The
 * guarded global shims in bootstrap-wp-stubs.php write here; the boot path runs without WordPress loaded.
 */
final class WordPressStubState {
	/** @var list<array{hook: string, callback: mixed}> */
	public static array $actions = array();

	/** @var list<array{hook: string, callback: mixed}> */
	public static array $filters = array();

	/** @var array<string, mixed> */
	public static array $options = array();

	/** @var list<string> */
	public static array $active_plugins = array();

	/** @var list<array{file: string, callback: mixed}> */
	public static array $activation_hooks = array();

	/** @var list<array{file: string, callback: mixed}> */
	public static array $deactivation_hooks = array();

	public static function reset(): void {
		self::$actions            = array();
		self::$filters            = array();
		self::$options            = array();
		self::$active_plugins     = array();
		self::$activation_hooks   = array();
		self::$deactivation_hooks = array();
	}

	public static function has_action( string $hook ): bool {
		foreach ( self::$actions as $action ) {
			if ( $action['hook'] === $hook ) {
				return true;
			}
		}

		return false;
	}

	public static function has_filter( string $hook ): bool {
		foreach ( self::$filters as $filter ) {
			if ( $filter['hook'] === $hook ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether an action was registered on $hook with the [$instance-of-$class, $method] callback.
	 *
	 * @param class-string $class
	 */
	public static function has_object_action( string $hook, string $class, string $method ): bool {
		foreach ( self::$actions as $action ) {
			$callback = $action['callback'];
			if ( $action['hook'] === $hook
				&& \is_array( $callback )
				&& isset( $callback[0], $callback[1] )
				&& $callback[0] instanceof $class
				&& $callback[1] === $method
			) {
				return true;
			}
		}

		return false;
	}

	/** @return array<string, mixed> */
	public static function option_array( string $key ): array {
		$value = self::$options[ $key ] ?? null;

		return \is_array( $value ) ? $value : array();
	}
}
