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

$dws_plugin_template_autoload         = __DIR__ . '/vendor/autoload.php';
$dws_plugin_template_scoped_autoload  = __DIR__ . '/dependencies/scoper-autoload.php';
$dws_plugin_template_scoped_bootstrap = __DIR__ . '/dependencies/ahegyes/wp-framework-bootstrap/functions.php';

/*
 * The Composer autoloader and the scoped framework output are both build artifacts of `composer
 * packages-install`. A partial install carrying one but not the other fatals on a require below, so a missing
 * build registers an admin notice — using no framework class, since the scoped framework is exactly what is
 * absent — instead of leaving the activated plugin silently inert.
 */
if ( dws_plugin_template_dependencies_missing(
	$dws_plugin_template_autoload,
	$dws_plugin_template_scoped_autoload,
	$dws_plugin_template_scoped_bootstrap
) ) {
	add_action( 'admin_notices', 'dws_plugin_template_render_setup_notice' );
	return;
}

if ( ! defined( 'DWS_PLUGIN_TEMPLATE_FILE' ) ) {
	define( 'DWS_PLUGIN_TEMPLATE_FILE', __FILE__ );
}
if ( ! defined( 'DWS_PLUGIN_TEMPLATE_VERSION' ) ) {
	define( 'DWS_PLUGIN_TEMPLATE_VERSION', '2.0.0' );
}

/*
 * The scoped framework uses PHP 8.0+ syntax that fails to compile below the framework floor, so the
 * requirements gate runs from the PHP 5.6-safe bootstrap package first; the full autoloader loads only once
 * PHP and WordPress clear the floor, keeping the requirements notice reachable on an unsupported runtime.
 */
require_once $dws_plugin_template_scoped_bootstrap;

$dws_plugin_template_requirements = \DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Bootstrap\Requirements\check_requirements(
	plugin_basename( __FILE__ )
);
if ( $dws_plugin_template_requirements instanceof \WP_Error ) {
	\DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Bootstrap\Notice\output_requirements_error(
		plugin_basename( __FILE__ ),
		$dws_plugin_template_requirements
	);
	return;
}
unset( $dws_plugin_template_requirements );

require_once $dws_plugin_template_autoload;
require_once __DIR__ . '/functions.php';

// Activation/deactivation must be wired during the include, before the plugins_loaded-deferred boot.
\DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\PluginKernel::register_lifecycle_hooks(
	\DeepWebSolutions\PluginTemplate\Plugin::get_instance()
);
add_action( 'plugins_loaded', 'dws_plugin_template_boot', 15 );

/**
 * Whether any `composer packages-install` build artifact the boot below requires is absent. A missing artifact
 * means a partial or skipped install, so the boot stops and the setup notice runs in its place. Takes its paths
 * as arguments and uses no framework class, so it stays callable before the autoloader and on any PHP the floor
 * notice must still reach.
 *
 * @since   2.0.0
 * @version 2.0.0
 *
 * @param   string $autoload  Path to the Composer autoloader.
 * @param   string $scoped    Path to the generated scoped-framework autoloader.
 * @param   string $bootstrap Path to the scoped bootstrap package's function aggregator.
 *
 * @return  bool
 */
function dws_plugin_template_dependencies_missing( $autoload, $scoped, $bootstrap ) {
	return ! is_file( $autoload ) || ! is_file( $scoped ) || ! is_file( $bootstrap );
}

/**
 * Renders the admin notice shown when the plugin is active but its Composer/scoped dependencies are unbuilt.
 * Self-contained — references no framework class — because the scoped framework is precisely what is missing.
 *
 * @since   2.0.0
 * @version 2.0.0
 *
 * @return  void
 */
function dws_plugin_template_render_setup_notice() {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__(
			'DWS Plugin Template is active but its dependencies are not installed. Run "composer packages-install" from the plugin directory to build them.',
			'dws-plugin-template'
		)
	);
}
