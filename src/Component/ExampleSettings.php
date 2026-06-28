<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Component;

use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Lifecycle\Hookable\HookableInterface;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Settings\Schema\ValueObjects\SettingsField;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Settings\Schema\ValueObjects\SettingsPage;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Settings\Schema\ValueObjects\SettingsSection;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\WooCommerce\Backend\WooCommerceSettingsBackend;

/**
 * Registers an example WooCommerce settings tab from a framework settings descriptor. Resolved only when
 * the WooCommerce Feature's conditionals pass, so it never loads when WooCommerce is absent.
 *
 * @since   2.0.0
 * @version 2.0.0
 */
final class ExampleSettings implements HookableInterface {
	// region MAGIC METHODS

	/**
	 * Constructor.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @param   WooCommerceSettingsBackend $backend Backend that registers the tab and persists each field.
	 */
	public function __construct(
		protected WooCommerceSettingsBackend $backend,
	) {}

	// endregion

	// region INHERITED METHODS

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	#[\Override]
	public function register_hooks(): void {
		// Defer to `init`: the descriptor's translated labels built below would otherwise run at plugins_loaded,
		// before the text domain is loadable, tripping WordPress 6.7+'s early-translation guard. WooCommerce
		// reads the registered tab only when rendering its settings screen, well after `init`.
		add_action( 'init', array( $this, 'register_settings_page' ) );
	}

	// endregion

	// region HOOKS

	/**
	 * Builds the settings descriptor and registers it as a WooCommerce tab. Hooked on `init`.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @return  void
	 */
	public function register_settings_page(): void {
		$this->backend->register_page( $this->build_page() );
	}

	// endregion

	// region HELPERS

	/**
	 * Builds the example settings page descriptor: one section carrying a checkbox and a text field.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @return  SettingsPage
	 */
	protected function build_page(): SettingsPage {
		return new SettingsPage(
			slug: 'dws_plugin_template',
			page_title: \__( 'DWS Plugin Template', 'dws-plugin-template' ),
			menu_title: \__( 'Plugin Template', 'dws-plugin-template' ),
			capability: 'manage_woocommerce',
			sections: array(
				new SettingsSection(
					'general',
					\__( 'General', 'dws-plugin-template' ),
					array(
						new SettingsField(
							id: 'enable_feature',
							type: 'checkbox',
							label: \__( 'Enable feature', 'dws-plugin-template' ),
							default_value: false,
							description: \__( 'Turn the example feature on.', 'dws-plugin-template' ),
						),
						new SettingsField(
							id: 'api_key',
							type: 'text',
							label: \__( 'API key', 'dws-plugin-template' ),
							default_value: '',
						),
					),
				),
			),
		);
	}

	// endregion
}
