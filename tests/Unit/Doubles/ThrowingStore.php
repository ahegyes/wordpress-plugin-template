<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Unit\Doubles;

use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Storage\KeyValueStoreInterface;

/**
 * Key-value store double whose clear() throws, standing in for a backend failure mid-uninstall so a test can
 * prove the multisite loop restores the blog context through its finally before the throwable propagates.
 *
 * @implements KeyValueStoreInterface<mixed>
 */
final class ThrowingStore implements KeyValueStoreInterface {
	#[\Override]
	public function set( string $key, mixed $value ): void {}

	#[\Override]
	public function get( string $key, mixed $default_value = null ): mixed {
		return $default_value;
	}

	#[\Override]
	public function has( string $key ): bool {
		return false;
	}

	#[\Override]
	public function delete( string $key ): bool {
		return false;
	}

	#[\Override]
	public function get_all(): array {
		return array();
	}

	#[\Override]
	public function clear(): void {
		throw new \RuntimeException( 'Simulated store failure.' );
	}
}
