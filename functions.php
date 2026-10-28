<?php declare( strict_types=1 );
/**
 * Defines the plugin's accessor, boots it on plugins_loaded, declares its WooCommerce feature compatibility and loads the files in includes/.
 *
 * @since   2.0.0
 * @version 2.0.0
 *
 * @package DeepWebSolutions\PluginTemplate
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use DeepWebSolutions\PluginTemplate\Plugin;

defined( 'ABSPATH' ) || exit;

add_action( 'plugins_loaded', 'dws_tpl_boot', 15 );
add_action( 'before_woocommerce_init', 'dws_tpl_declare_compatibility' );

$dws_tpl_includes = glob( __DIR__ . '/includes/*.php' );
if ( false === $dws_tpl_includes ) {
	throw new RuntimeException( 'The includes directory could not be read.' );
}

sort( $dws_tpl_includes );
foreach ( $dws_tpl_includes as $dws_tpl_include ) {
	if ( ! str_starts_with( basename( $dws_tpl_include ), '_' ) ) { // An underscore prefix keeps a file from loading.
		require_once $dws_tpl_include;
	}
}

/**
 * Returns the plugin's composition root, built on first use.
 *
 * @since   2.0.0
 * @version 2.0.0
 *
 * @return  Plugin
 */
function dws_tpl_plugin(): Plugin {
	static $plugin = null;
	if ( ! $plugin instanceof Plugin ) {
		$plugin = new Plugin();
	}

	return $plugin;
}

/**
 * Boots the plugin, reporting a composition root that cannot be built as an incomplete build.
 *
 * @since   2.0.0
 * @version 2.0.0
 *
 * @return  void
 */
function dws_tpl_boot(): void {
	try {
		dws_tpl_plugin()->boot();
	} catch ( Throwable $error ) {
		dws_tpl_handle_build_error( $error );
	}
}

/**
 * Declares whether the plugin works with WooCommerce's order tables, which each plugin built from the template sets from its own evidence.
 *
 * @since   2.0.0
 * @version 2.0.0
 *
 * @return  void
 */
function dws_tpl_declare_compatibility(): void {
	FeaturesUtil::declare_compatibility( 'custom_order_tables', DWS_TPL_FILE, false );
}
