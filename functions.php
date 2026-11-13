<?php declare( strict_types=1 );
/**
 * Loads the files in includes/, defines the plugin's accessor, boots the plugin on plugins_loaded, declares its WooCommerce feature compatibility and unschedules its jobs on deactivation.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @package DeepWebSolutions\PluginTemplate
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use DeepWebSolutions\PluginTemplate\Plugin;

defined( 'ABSPATH' ) || exit;

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

// The hooks come last, so an include that throws leaves nothing of the plugin hooked.
add_action(
	'plugins_loaded',
	static function (): void {
		dws_tpl_plugin()->boot();
	},
	15
);
add_action(
	'before_woocommerce_init',
	static function (): void {
		FeaturesUtil::declare_compatibility( 'custom_order_tables', DWS_TPL_FILE, false ); // Each WooCommerce feature flag is declared from evidence.
	}
);
register_deactivation_hook(
	DWS_TPL_FILE,
	static function (): void {
		dws_tpl_plugin()->deactivate();
	}
);

/**
 * Returns the plugin's composition root, built on first use.
 *
 * @since   1.0.0
 * @version 1.0.0
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
