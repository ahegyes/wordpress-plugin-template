<?php declare( strict_types=1 );

/**
 * Global plugin functions: the public instance accessor for theme / snippet developers, plus the
 * internal `plugins_loaded` boot callback. Thin delegators only, no business logic.
 *
 * @since   2.0.0
 * @version 2.0.0
 *
 * @package DeepWebSolutions\PluginTemplate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns the plugin's singleton instance.
 *
 * @since   2.0.0
 * @version 2.0.0
 *
 * @return  \DeepWebSolutions\PluginTemplate\Plugin
 */
function dws_plugin_template_instance(): \DeepWebSolutions\PluginTemplate\Plugin {
	return \DeepWebSolutions\PluginTemplate\Plugin::get_instance();
}

/**
 * Boots the plugin on `plugins_loaded` priority 15. Internal wiring, not a public extension point.
 *
 * @since   2.0.0
 * @version 2.0.0
 *
 * @return  void
 */
function dws_plugin_template_boot(): void {
	dws_plugin_template_instance()->boot();
}
