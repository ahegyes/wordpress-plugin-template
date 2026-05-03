<?php declare( strict_types=1 );

/**
 * Plugin Name:       DWS Plugin Template
 * Plugin URI:        https://github.com/ahegyes/wordpress-plugin-template
 * Description:       Starting point for WordPress plugins built on the DWS framework.
 * Version:           2.0.0
 * Requires PHP:      8.5
 * Requires at least: 7.0
 * Author:            Contributors
 * Author URI:        https://github.com/ahegyes
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dws-plugin-template
 * Domain Path:       /languages
 *
 * @since   2.0.0
 * @version 2.0.0
 *
 * @package DeepWebSolutions\PluginTemplate
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_file( __DIR__ . '/vendor/autoload.php' ) ) {
	return;
}

if ( ! defined( 'DWS_PLUGIN_TEMPLATE_FILE' ) ) {
	define( 'DWS_PLUGIN_TEMPLATE_FILE', __FILE__ );
}
if ( ! defined( 'DWS_PLUGIN_TEMPLATE_VERSION' ) ) {
	define( 'DWS_PLUGIN_TEMPLATE_VERSION', '2.0.0' );
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/functions.php';

$dws_plugin_template_requirements = \DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Bootstrap\check_requirements(
	plugin_basename( __FILE__ )
);
if ( true !== $dws_plugin_template_requirements ) {
	\DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Bootstrap\output_requirements_error(
		plugin_basename( __FILE__ ),
		$dws_plugin_template_requirements
	);
	return;
}
unset( $dws_plugin_template_requirements );

add_action( 'plugins_loaded', 'dws_plugin_template_boot', 15 );
register_activation_hook( DWS_PLUGIN_TEMPLATE_FILE, 'dws_plugin_template_activate' );
register_deactivation_hook( DWS_PLUGIN_TEMPLATE_FILE, 'dws_plugin_template_deactivate' );
