<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Greeting;

use DeepWebSolutions\PluginTemplate\Options;
use DeepWebSolutions\PluginTemplate\Plugin;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Settings\Forms;

\defined( 'ABSPATH' ) || exit;

/**
 * Declares the greeting's settings fields, which the plugin's settings tab shows.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @phpstan-import-type Field from Forms
 */
final class GreetingSettings {
	// region MAGIC METHODS

	/**
	 * Constructor.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	protected function __construct() {}

	// endregion

	// region METHODS

	/**
	 * Returns the greeting's settings fields, under a title of their own.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  list<Field>
	 */
	public static function get_settings_fields(): array {
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
				'default' => Options::DEFAULTS[ Options::GREETING_ENABLED ],
			),
			array(
				'type'        => 'text',
				'id'          => Options::GREETING,
				'title'       => __( 'Message', 'dws-plugin-template' ),
				'desc'        => __( 'Leave empty to show the default greeting in each customer\'s language.', 'dws-plugin-template' ),
				'default'     => Options::DEFAULTS[ Options::GREETING ],
				'placeholder' => AccountDashboardGreeting::get_default_message(), // A default would be saved in the admin's language with the first save of the tab.
			),
			array(
				'type'              => 'number',
				'id'                => Options::GREETING_ORDERS,
				'title'             => __( 'Orders before the greeting', 'dws-plugin-template' ),
				'desc'              => __( 'Customers see the greeting once this many of their orders are paid.', 'dws-plugin-template' ),
				'default'           => Options::DEFAULTS[ Options::GREETING_ORDERS ],
				'placeholder'       => Options::DEFAULTS[ Options::GREETING_ORDERS ],
				'custom_attributes' => array(
					'min'  => '1',
					'step' => '1',
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => Plugin::PREFIX . '_greeting_options',
			),
		);
	}

	// endregion
}
