<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Installer;

use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Installer\InstallerInterface;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Shared\Version\Version;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Storage\OptionsStore;

/**
 * Plugin installer. Owns one wp_options row holding the stored version and the install marker; the kernel
 * drives install() and update() on boot from the stored-versus-current comparison and advances the stored
 * version only on success, so every step must be idempotent.
 *
 * @since   2.0.0
 * @version 2.0.0
 */
final class Installer implements InstallerInterface {
	// region FIELDS AND CONSTANTS

	/**
	 * Store key holding the recorded plugin version.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @var     string
	 */
	protected const VERSION_KEY = 'version';

	/**
	 * Store key holding the first-install timestamp.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @var     string
	 */
	protected const INSTALLED_AT_KEY = 'installed_at';

	// endregion

	// region MAGIC METHODS

	/**
	 * Constructor.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @param   OptionsStore<mixed> $store Backing wp_options store for installer state.
	 */
	public function __construct(
		protected OptionsStore $store,
	) {}

	// endregion

	// region INHERITED METHODS

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	#[\Override]
	public function install(): void {
		// Idempotent seed: a retry after a failed boot keeps the original timestamp rather than resetting it.
		if ( ! $this->store->has( self::INSTALLED_AT_KEY ) ) {
			$this->store->set( self::INSTALLED_AT_KEY, time() );
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	#[\Override]
	public function update( Version $from_version ): void {
		// Each step is guarded by the version that introduced it and must converge on re-run: a failed boot
		// re-runs update() from the same $from_version, since the stored version advances only on success.
		if ( $from_version->is_less_than( Version::from_string( '2.1.0' ) ) ) {
			$this->store->set( 'schema', 2 );
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	#[\Override]
	public function get_current_version(): Version {
		return Version::from_string( DWS_PLUGIN_TEMPLATE_VERSION );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	#[\Override]
	public function get_stored_version(): ?Version {
		$stored = $this->store->get( self::VERSION_KEY );

		return \is_string( $stored ) && '' !== $stored ? Version::from_string( $stored ) : null;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	#[\Override]
	public function set_stored_version( Version $version ): void {
		$this->store->set( self::VERSION_KEY, $version->value );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	#[\Override]
	public function activate( bool $network_wide = false ): void {
		// No activation work: the kernel runs install()/update() on every boot, so first-run setup needs no
		// separate activation step. Capability grants, when a plugin has them, would live here and in update().
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	#[\Override]
	public function deactivate( bool $network_deactivating = false ): void {
		// No deactivation work: stored state persists across a deactivate/reactivate cycle, matching WordPress norms.
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	#[\Override]
	public function uninstall(): void {
		// Remove every persistent footprint the plugin creates: the installer's own state, the persistent
		// admin-notice store, the example WooCommerce settings options, and the per-user dismissal records.
		$this->store->clear();
		\delete_option( 'dws_plugin_template_notices' );
		\delete_option( 'dws_plugin_template_enable_feature' );
		\delete_option( 'dws_plugin_template_api_key' );
		\delete_metadata( 'user', 0, 'dws_plugin_template_dismissed_notices', '', true );
	}

	// endregion
}
