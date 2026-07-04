<?php declare( strict_types=1 );

/**
 * Single source of the plugin's persistent footprint: every wp_options row and user-meta key the plugin
 * writes, each built from the slug declared once below. Framework-free and autoload-free on purpose —
 * `config/container.php` derives the live wiring from the named entries, and `uninstall.php`'s degraded
 * no-build fallback requires this file directly, so the two can never desync.
 *
 * @since   2.0.0
 * @version 2.0.0
 *
 * @package DeepWebSolutions\PluginTemplate
 */

$dws_plugin_template_slug = 'dws_plugin_template';

// The WooCommerce settings backend persists one wp_options row per field ({slug}_{field}); the WordPress
// settings backend persists one grouped row per section ({slug}-{section_id}). Keep these lists aligned
// with the ExampleSettings and ExampleWPSettings descriptors.
$dws_plugin_template_wc_settings_options = array(
	$dws_plugin_template_slug . '_enable_feature',
	$dws_plugin_template_slug . '_greeting',
);
$dws_plugin_template_wp_settings_options = array(
	$dws_plugin_template_slug . '-general',
);

$dws_plugin_template_installer_option = $dws_plugin_template_slug;
$dws_plugin_template_notices_option   = $dws_plugin_template_slug . '_notices';
$dws_plugin_template_dismissals_meta  = $dws_plugin_template_slug . '_dismissed_notices';

return array(
	'slug'                   => $dws_plugin_template_slug,
	'installer_option'       => $dws_plugin_template_installer_option,
	'notices_option'         => $dws_plugin_template_notices_option,
	'settings_options'       => array_merge( $dws_plugin_template_wc_settings_options, $dws_plugin_template_wp_settings_options ),
	'dismissed_notices_meta' => $dws_plugin_template_dismissals_meta,

	// Flat lists over the named entries above, for consumers that only delete: the uninstall fallback
	// iterates these, so every named option/meta key must feed one of the two.
	'options'                => array_merge(
		array( $dws_plugin_template_installer_option, $dws_plugin_template_notices_option ),
		$dws_plugin_template_wc_settings_options,
		$dws_plugin_template_wp_settings_options
	),
	'user_meta'              => array( $dws_plugin_template_dismissals_meta ),
);
