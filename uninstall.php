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
	// Autoload-independent fallback: with the framework unbuilt the installer is unreachable, so delete the
	// known footprint directly rather than leaving it behind. These keys duplicate config/container.php
	// because the container is unreachable here. This degraded path clears only the current site; a built
	// install runs the multisite-aware installer.
	delete_option( 'dws_plugin_template' );
	delete_option( 'dws_plugin_template_notices' );
	delete_option( 'dws_plugin_template_enable_feature' );
	delete_option( 'dws_plugin_template_greeting' );
	delete_metadata( 'user', 0, 'dws_plugin_template_dismissed_notices', '', true );
	return;
}

require_once __DIR__ . '/vendor/autoload.php';

\DeepWebSolutions\PluginTemplate\Plugin::get_instance()->get_installer()->uninstall();
