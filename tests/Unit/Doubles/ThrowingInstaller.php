<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Unit\Doubles;

use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Installer\InstallerInterface;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Shared\Version\Version;

/**
 * Installer double whose install() throws, standing in for a broken first-install or migration so a test can
 * drive the kernel's fail-closed boot path. get_stored_version() returns null, so the kernel takes install().
 */
final class ThrowingInstaller implements InstallerInterface {
	#[\Override]
	public function install(): void {
		throw new \RuntimeException( 'Simulated install failure.' );
	}

	#[\Override]
	public function update( Version $from_version ): void {}

	#[\Override]
	public function get_current_version(): Version {
		return Version::from_string( '2.0.0' );
	}

	#[\Override]
	public function get_stored_version(): ?Version {
		return null;
	}

	#[\Override]
	public function set_stored_version( Version $version ): void {}

	#[\Override]
	public function activate( bool $network_wide = false ): void {}

	#[\Override]
	public function deactivate( bool $network_deactivating = false ): void {}

	#[\Override]
	public function uninstall(): void {}
}
