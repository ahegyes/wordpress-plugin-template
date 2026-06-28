<?php declare( strict_types=1 );

/**
 * Uninstall handler. Runs in WordPress's cold uninstall bootstrap — no plugin loaded, only
 * WP_UNINSTALL_PLUGIN defined — so it rebuilds the container and delegates cleanup to the installer.
 *
 * @since   2.0.0
 * @version 2.0.0
 *
 * @package DeepWebSolutions\PluginTemplate
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! is_file( __DIR__ . '/vendor/autoload.php' ) ) {
	return;
}

require_once __DIR__ . '/vendor/autoload.php';

\DeepWebSolutions\PluginTemplate\Plugin::get_instance()->get_installer()->uninstall();
