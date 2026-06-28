<?php declare( strict_types=1 );

/**
 * Mock-WordPress shims for the Unit boot smoke. Defines the plugin constants and the WordPress functions
 * the boot path calls, each guarded so wp-env's real WordPress wins when present. Excluded from static
 * analysis (see phpstan.dist.neon); recorded calls are asserted through WordPressStubState.
 */

require_once __DIR__ . '/WordPressStubState.php';

use DeepWebSolutions\PluginTemplate\Tests\Unit\WordPressStubState;

if ( ! defined( 'DWS_PLUGIN_TEMPLATE_FILE' ) ) {
	define( 'DWS_PLUGIN_TEMPLATE_FILE', '/var/www/html/wp-content/plugins/dws-plugin-template/dws-plugin-template.php' );
}
if ( ! defined( 'DWS_PLUGIN_TEMPLATE_VERSION' ) ) {
	define( 'DWS_PLUGIN_TEMPLATE_VERSION', '2.0.0' );
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		WordPressStubState::$actions[] = array(
			'hook'     => (string) $hook,
			'callback' => $callback,
		);
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		WordPressStubState::$filters[] = array(
			'hook'     => (string) $hook,
			'callback' => $callback,
		);
		return true;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default_value = false ) {
		return array_key_exists( $option, WordPressStubState::$options )
			? WordPressStubState::$options[ $option ]
			: $default_value;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) {
		WordPressStubState::$options[ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $option ) {
		unset( WordPressStubState::$options[ $option ] );
		return true;
	}
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	function is_plugin_active( $plugin ) {
		return in_array( $plugin, WordPressStubState::$active_plugins, true );
	}
}

if ( ! function_exists( 'register_activation_hook' ) ) {
	function register_activation_hook( $file, $callback ) {
		WordPressStubState::$activation_hooks[] = array(
			'file'     => (string) $file,
			'callback' => $callback,
		);
	}
}

if ( ! function_exists( 'register_deactivation_hook' ) ) {
	function register_deactivation_hook( $file, $callback ) {
		WordPressStubState::$deactivation_hooks[] = array(
			'file'     => (string) $file,
			'callback' => $callback,
		);
	}
}
