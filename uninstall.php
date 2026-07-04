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

/**
 * Deletes the plugin's persisted footprint directly, without the framework.
 *
 * This degraded path clears only the current site; a built install runs the multisite-aware installer.
 *
 * @since   2.0.0
 * @version 2.0.0
 */
function dws_plugin_template_delete_footprint(): void {
	$footprint = require __DIR__ . '/config/footprint.php';

	foreach ( $footprint['options'] as $option ) {
		delete_option( $option );
	}
	foreach ( $footprint['user_meta'] as $meta_key ) {
		delete_metadata( 'user', 0, $meta_key, '', true );
	}
}

/*
 * The container path needs the same three build artifacts as the entry point's preflight — a partial build
 * (vendor/ present but the scoped framework absent) would fatal resolving the installer's scoped classes.
 * With any artifact missing the installer is unreachable, so delete the footprint directly rather than
 * leaving it behind.
 */
if ( ! is_file( __DIR__ . '/vendor/autoload.php' )
	|| ! is_file( __DIR__ . '/dependencies/scoper-autoload.php' )
	|| ! is_file( __DIR__ . '/dependencies/ahegyes/wp-framework-bootstrap/functions.php' )
) {
	dws_plugin_template_delete_footprint();

	return;
}

try {
	require_once __DIR__ . '/vendor/autoload.php';

	\DeepWebSolutions\PluginTemplate\Plugin::get_instance()->get_installer()->uninstall();
} catch ( \Throwable ) {
	// The artifacts exist but the container path still failed (a missing or unparsable scoped class,
	// a below-floor PHP runtime) — the framework-free deletes must run so the footprint never survives.
	dws_plugin_template_delete_footprint();
}
