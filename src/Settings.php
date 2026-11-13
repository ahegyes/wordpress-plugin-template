<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate;

use DeepWebSolutions\PluginTemplate\Greeting\AccountDashboardGreeting;
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
			array(
				'type'  => 'title',
				'id'    => Plugin::PREFIX . '_greeting_options',
				'title' => __( 'Greeting', 'dws-plugin-template' ),
				'desc'  => __( 'Greets returning customers on their account dashboard.', 'dws-plugin-template' ),
			),
			array(
				'type'    => 'checkbox',
				'id'      => Options::GREETING_ENABLED,
				'title'   => __( 'Show the greeting', 'dws-plugin-template' ),
				'desc'    => __( 'Greet returning customers', 'dws-plugin-template' ),
				'default' => wc_bool_to_string( Options::DEFAULTS[ Options::GREETING_ENABLED ] ),
			),
			array(
				'type'        => 'text',
				'id'          => Options::GREETING,
				'title'       => __( 'Message', 'dws-plugin-template' ),
				'desc'        => __( 'Leave empty to show the default greeting in each customer\'s language.', 'dws-plugin-template' ),
				'default'     => '',
				'placeholder' => AccountDashboardGreeting::get_default_message(), // A default would be saved in the admin's language with the first save of the tab.
			),
			array(
				'type'              => 'number',
				'id'                => Options::GREETING_ORDERS,
				'title'             => __( 'Orders before the greeting', 'dws-plugin-template' ),
				'desc'              => __( 'Customers see the greeting once this many of their orders are paid.', 'dws-plugin-template' ),
				'default'           => (string) Options::DEFAULTS[ Options::GREETING_ORDERS ],
				'custom_attributes' => array(
					'min'  => '1',
					'step' => '1',
				),
				'validate'          => self::validate_greeting_orders( ... ),
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
				'id'      => Options::DELETE_DATA,
				'title'   => __( 'Remove all data on uninstallation', 'dws-plugin-template' ),
				'desc'    => __( 'Delete the plugin\'s data when it is uninstalled, so it must be configured again if it is installed later', 'dws-plugin-template' ),
				'default' => wc_bool_to_string( Options::DEFAULTS[ Options::DELETE_DATA ] ),
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

	// region HELPERS

	/**
	 * Returns the number of orders before the greeting shows as the digits WooCommerce stores, or an error when the submission is not a whole number of at least 1.
	 *
	 * The number input sends a whole number in any form a browser accepts, such as 03 or 3.0, so the submission parses as a float.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   mixed $submitted The submitted value.
	 *
	 * @return  string|\WP_Error
	 */
	protected static function validate_greeting_orders( mixed $submitted ): string|\WP_Error {
		$orders = \is_string( $submitted ) ? \filter_var( $submitted, \FILTER_VALIDATE_FLOAT ) : false;
		if ( false === $orders || $orders < 1 || $orders >= \PHP_INT_MAX || \floor( $orders ) !== $orders ) {
			return new \WP_Error( 'invalid_number', __( 'Enter a whole number of 1 or more, such as 3.', 'dws-plugin-template' ) );
		}

		return (string) (int) $orders;
	}

	// endregion
}
