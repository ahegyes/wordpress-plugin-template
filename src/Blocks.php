<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate;

use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Asset\Assets;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\ComponentInterface;

\defined( 'ABSPATH' ) || exit;

/**
 * Registers the plugin's blocks from their build manifest, and the block-editor script.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class Blocks implements ComponentInterface {
	// region MAGIC METHODS

	/**
	 * Constructor.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   Assets $assets The registrar of the plugin's built scripts and styles.
	 */
	public function __construct(
		protected Assets $assets
	) {}

	// endregion

	// region INHERITED METHODS

	/**
	 * {@inheritDoc}
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	#[\Override]
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_script' ) );
	}

	// endregion

	// region HOOKS

	/**
	 * Registers every built block from the manifest, which reads all their metadata from one file.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function register_blocks(): void {
		wp_register_block_types_from_metadata_collection( plugin_dir_path( DWS_TPL_FILE ) . 'blocks/build', plugin_dir_path( DWS_TPL_FILE ) . 'blocks/build/blocks-manifest.php' );
	}

	/**
	 * Enqueues the block-editor script.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function enqueue_editor_script(): void {
		if ( $this->assets->register_script( Plugin::SLUG . '-editor', 'assets/build/editor.js' ) ) {
			wp_enqueue_script( Plugin::SLUG . '-editor' );
		}
	}

	// endregion
}
