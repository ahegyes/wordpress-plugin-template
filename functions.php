<?php declare( strict_types=1 );

/**
 * Plugin facade for theme / snippet developers — thin delegators only, no business logic.
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
 * Boots the plugin — invoked on `plugins_loaded` priority 15.
 *
 * @since   2.0.0
 * @version 2.0.0
 *
 * @return  void
 */
function dws_plugin_template_boot(): void {
	dws_plugin_template_instance()->boot();
}
