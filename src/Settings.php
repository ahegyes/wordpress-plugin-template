<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate;

use DeepWebSolutions\PluginTemplate\Greeting\GreetingSettings;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\ComponentInterface;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\WooCommerce\Setting\SectionFieldsDefinitionInterface;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\WooCommerce\Setting\SettingsTab;

\defined( 'ABSPATH' ) || exit;

/**
 * Adds the plugin's WooCommerce settings tab.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class Settings implements ComponentInterface, SectionFieldsDefinitionInterface {
	// region INHERITED METHODS

	/**
	 * {@inheritDoc}
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	#[\Override]
	public function register_hooks(): void {
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_tab' ) );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	#[\Override]
	public function get_section_fields( string $tab_id, string $section_id ): array {
		return array(
			...GreetingSettings::get_settings_fields(),
			array(
				'type'  => 'title',
				'id'    => Plugin::PREFIX . '_data_options',
				'title' => __( 'Data', 'dws-plugin-template' ),
			),
			array(
				'type'    => 'checkbox',
				'id'      => Options::DELETE_DATA,
				'title'   => __( 'Remove all data on uninstallation', 'dws-plugin-template' ),
				'desc'    => __( 'Delete the plugin\'s data when it is uninstalled, so it must be configured again if it is installed later', 'dws-plugin-template' ),
				'default' => Options::DEFAULTS[ Options::DELETE_DATA ],
			),
			array(
				'type' => 'sectionend',
				'id'   => Plugin::PREFIX . '_data_options',
			),
		);
	}

	// endregion

	// region HOOKS

	/**
	 * Adds the plugin's settings tab, which WooCommerce's settings page class must exist to construct.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   array<int, \WC_Settings_Page> $pages The settings tabs.
	 *
	 * @return  array<int, \WC_Settings_Page>
	 */
	public function add_settings_tab( array $pages ): array {
		$pages[] = new SettingsTab(
			Plugin::PREFIX,
			array(
				'label'    => __( 'DWS Plugin Template', 'dws-plugin-template' ),
				'sections' => array( '' => __( 'General', 'dws-plugin-template' ) ),
			),
			$this
		);
		return $pages;
	}

	// endregion
}
