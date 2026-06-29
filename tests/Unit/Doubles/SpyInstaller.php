<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Unit\Doubles;

use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Installer\InstallerInterface;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Shared\Version\Version;

/**
 * Installer double recording the network flag each activate()/deactivate() call receives, so a test can prove
 * the lifecycle hooks the entrypoint wires actually route to the installer rather than merely being counted.
 */
final class SpyInstaller implements InstallerInterface {
	/** @var list<bool> */
	public array $activations = array();

	/** @var list<bool> */
	public array $deactivations = array();

	#[\Override]
	public function install(): void {}

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
	public function activate( bool $network_wide = false ): void {
		$this->activations[] = $network_wide;
	}

	#[\Override]
	public function deactivate( bool $network_deactivating = false ): void {
		$this->deactivations[] = $network_deactivating;
	}

	#[\Override]
	public function uninstall(): void {}
}
