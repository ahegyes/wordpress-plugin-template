<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate;

use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\ComponentInterface;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\WooCommerce\Setting\SectionFieldsDefinitionInterface;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\WooCommerce\Setting\SettingsTab;

\defined( 'ABSPATH' ) || exit;

/**
 * Adds the plugin's WooCommerce settings tab, which holds the greeting's settings and the consent to delete the plugin's data on uninstall.
 *
 * @since   2.0.0
 * @version 2.0.0
 */
final class Settings implements ComponentInterface, SectionFieldsDefinitionInterface {
	// region INHERITED METHODS

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	#[\Override]
	public function register_hooks(): void {
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_tab' ) );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	#[\Override]
	public function get_section_fields( string $tab_id, string $section_id ): array {
		return array(
			array(
				'type'  => 'title',
				'id'    => Plugin::PREFIX . '_greeting_options',
				'title' => __( 'Greeting', 'dws-plugin-template' ),
				'desc'  => __( 'Greets returning customers on their account dashboard.', 'dws-plugin-template' ),
			),
			array(
				'type'    => 'checkbox',
				'id'      => DWS_TPL_OPTION_FEATURE,
				'title'   => __( 'Greeting', 'dws-plugin-template' ),
				'desc'    => __( 'Greet returning customers', 'dws-plugin-template' ),
				'default' => wc_bool_to_string( DWS_TPL_OPTION_DEFAULTS[ DWS_TPL_OPTION_FEATURE ] ),
			),
			array(
				'type'        => 'text',
				'id'          => DWS_TPL_OPTION_GREETING,
				'title'       => __( 'Message', 'dws-plugin-template' ),
				'default'     => '',
				'placeholder' => dws_tpl_get_option_text( DWS_TPL_OPTION_GREETING ), // A default would be saved in the admin's language with the first save of the tab.
			),
			array(
				'type'              => 'number',
				'id'                => DWS_TPL_OPTION_GREETING_ORDERS,
				'title'             => __( 'Orders before the greeting', 'dws-plugin-template' ),
				'desc'              => __( 'Customers see the greeting once they have placed this many orders.', 'dws-plugin-template' ),
				'default'           => (string) DWS_TPL_OPTION_DEFAULTS[ DWS_TPL_OPTION_GREETING_ORDERS ],
				'custom_attributes' => array(
					'min'  => '1',
					'step' => '1',
				),
				'validate'          => 'dws_tpl_validate_greeting_orders',
			),
			array(
				'type' => 'sectionend',
				'id'   => Plugin::PREFIX . '_greeting_options',
			),
			array(
				'type'  => 'title',
				'id'    => Plugin::PREFIX . '_data_options',
				'title' => __( 'Data', 'dws-plugin-template' ),
			),
			array(
				'type'    => 'checkbox',
				'id'      => DWS_TPL_OPTION_DELETE_DATA,
				'title'   => __( 'Data on uninstall', 'dws-plugin-template' ),
				'desc'    => __( 'Delete the plugin\'s data when it is uninstalled', 'dws-plugin-template' ),
				'default' => wc_bool_to_string( DWS_TPL_OPTION_DEFAULTS[ DWS_TPL_OPTION_DELETE_DATA ] ),
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
	 * @since   2.0.0
	 * @version 2.0.0
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
