<?php declare( strict_types=1 );
/**
 * Loads the plugin's safeguards, then the rest of the plugin once the site meets its floors, showing a notice where its build is incomplete.
 *
 * Plugin Name:          DWS Plugin Template
 * Plugin URI:           https://github.com/ahegyes/wordpress-plugin-template
 * Description:          WordPress plugins on the wp-framework packages start here, from one tested anatomy, so all are built alike and a fix found in one applies to all.
 * Version:              1.0.0
 * Requires at least:    7.1
 * Tested up to:         7.1
 * Requires PHP:         8.5
 * Requires Plugins:     woocommerce
 * WC requires at least: 11.3
 * WC tested up to:      11.3
 * Author:               Contributors
 * Author URI:           https://github.com/ahegyes
 * License:              GPL-3.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-3.0.html
 * Update URI:           https://github.com/ahegyes/wordpress-plugin-template
 * Text Domain:          dws-plugin-template
 * Domain Path:          /languages
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @package DeepWebSolutions\PluginTemplate
 */

use DeepWebSolutions\PluginTemplate\Safeguards;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Bootstrap\Requirements;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Bootstrap\Updater;

defined( 'ABSPATH' ) || exit;

define( 'DWS_TPL_FILE', __FILE__ );
load_plugin_textdomain( 'dws-plugin-template', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- WordPress registers languages/ itself only for plugins it does not network-activate.

require_once __DIR__ . '/Safeguards.php'; // Before the floor gate, so the restrictions hold on any runtime that parses the plugin.
Safeguards::register();

try {
	$dws_tpl_bootstrap = __DIR__ . '/vendor-prefixed/ahegyes/wp-framework-bootstrap/load.php';
	if ( ! is_readable( $dws_tpl_bootstrap ) ) { // The floor gate lives in the bootstrap package, so nothing built may load without it.
		throw new RuntimeException( 'The bootstrap package is missing.' );
	}

	require_once $dws_tpl_bootstrap;
	Updater::register( __FILE__ ); // Before the floor gate, so a release that lowers the floors still reaches a site below them.
	if ( ! Requirements::check_floors( __FILE__ ) ) {
		return;
	}

	if ( ! is_readable( __DIR__ . '/blocks/build/blocks-manifest.php' ) || ! is_readable( __DIR__ . '/assets/build/editor.asset.php' ) ) { // A checkout that skipped the script build would load without its blocks and scripts.
		throw new RuntimeException( 'The blocks or the scripts are not built.' );
	}

	require_once __DIR__ . '/vendor/autoload.php';
	require_once __DIR__ . '/functions.php';
} catch ( Throwable $error ) { // PHP throws an Error for a missing required file, so an incomplete build lands here.
	if ( function_exists( 'wp_get_environment_type' ) && in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
		throw $error;
	}

	add_action(
		'all_admin_notices',
		static function (): void {
			if ( current_user_can( 'activate_plugins' ) ) {
				printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html__( 'DWS Plugin Template is missing some of its files, so its features stay off until the plugin is reinstalled.', 'dws-plugin-template' ) );
			}
		}
	);
}
