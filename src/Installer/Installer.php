<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Installer;

use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Installer\InstallerInterface;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Shared\Version\Version;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Storage\KeyValueStoreInterface;

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
	 * @param   KeyValueStoreInterface<mixed> $store               Backing store for installer state.
	 * @param   list<string>                  $footprint_options   wp_options keys, beyond the store's own row, removed on uninstall.
	 * @param   list<string>                  $footprint_user_meta User-meta keys removed on uninstall (network-global).
	 */
	public function __construct(
		protected readonly KeyValueStoreInterface $store,
		protected readonly array $footprint_options = array(),
		protected readonly array $footprint_user_meta = array(),
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

		// Capability grants, when a plugin has them, live here and in update() (revoked on uninstall()) —
		// capabilities persist across a deactivate/reactivate cycle, so they are never tied to activate().
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	#[\Override]
	public function update( Version $from_version ): void {
		// Gate each migration step by the version that introduced it, reading $from_version so a step runs
		// only for installs older than that version, and keep every step idempotent: a failed boot re-runs
		// update() from the same $from_version, since the stored version advances only on success. The step
		// version must be at or below get_current_version() so the step ships in the release that runs it.
		// This template carries no schema to migrate; a real step guards a one-time idempotent block with
		// `if ( $from_version->is_less_than( Version::from_string( '1.5.0' ) ) )`.
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
		// No activation work, so $network_wide needs no get_sites() loop: the kernel runs install()/update()
		// on every boot, and boot runs per-request on each site, so first-run setup happens per site without a
		// separate activation step.
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
		// A network uninstall fires once for the whole network, so the per-site option rows must be cleared on
		// each site in turn; a single-site install clears its one site directly.
		if ( \is_multisite() ) {
			$site_ids = \get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);
			if ( \is_array( $site_ids ) ) {
				foreach ( $site_ids as $site_id ) {
					\switch_to_blog( (int) $site_id );
					try {
						$this->delete_site_footprint();
					} finally {
						// restore_current_blog() runs even when a site's deletion throws, so a failed delete
						// leaves the blog context as it was found rather than stranded on the switched site.
						\restore_current_blog();
					}
				}
			}
		} else {
			$this->delete_site_footprint();
		}

		// User metadata is network-global, so the per-user dismissal records clear once regardless of multisite.
		foreach ( $this->footprint_user_meta as $meta_key ) {
			\delete_metadata( 'user', 0, $meta_key, '', true );
		}
	}

	// endregion

	// region HELPERS

	/**
	 * Removes the plugin's per-site persistent footprint on the current site: the installer's own state row
	 * and every option declared at construction. Runs once per site under a multisite uninstall.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @return  void
	 */
	protected function delete_site_footprint(): void {
		$this->store->clear();
		foreach ( $this->footprint_options as $option_key ) {
			\delete_option( $option_key );
		}
	}

	// endregion
}
