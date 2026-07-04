<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Component;

use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Lifecycle\Hookable\HookableInterface;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Settings\Backend\WordPressSettingsBackend;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Settings\Schema\ValueObjects\SettingsField;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Settings\Schema\ValueObjects\SettingsPage;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Settings\Schema\ValueObjects\SettingsSection;

/**
 * Registers an example native-WordPress options page from a framework settings descriptor. Lives in the
 * always-on GenericFeature, so it demonstrates the WordPress settings backend with no WooCommerce anywhere
 * near it. The backend persists the section as one grouped wp_options row ({slug}-{section_id}); that row
 * is part of the uninstall footprint in config/footprint.php.
 *
 * @since   2.0.0
 * @version 2.0.0
 */
final class ExampleWPSettings implements HookableInterface {
	// region MAGIC METHODS

	/**
	 * Constructor.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @param   WordPressSettingsBackend $backend Backend that registers the options page and persists each section.
	 */
	public function __construct(
		protected readonly WordPressSettingsBackend $backend,
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
		// before the text domain is loadable, tripping WordPress 6.7+'s early-translation guard. The backend's
		// own admin_menu / admin_init / rest_api_init hooks all fire after `init`.
		add_action( 'init', array( $this, 'register_settings_page' ) );
	}

	// endregion

	// region HOOKS

	/**
	 * Builds the settings descriptor and registers it with the WordPress backend. Hooked on `init`.
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
	 * Builds the example options-page descriptor: one section under Settings, carrying a text field with a
	 * custom sanitize seam, a clamped number field, and a select.
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
			menu_title: \__( 'DWS Plugin Template', 'dws-plugin-template' ),
			capability: 'manage_options',
			// null location defaults to options-general.php: the page lands under the Settings menu.
			sections: array(
				new SettingsSection(
					id: 'general',
					title: \__( 'General', 'dws-plugin-template' ),
					fields: array(
						new SettingsField(
							id: 'welcome_text',
							type: 'text',
							label: \__( 'Welcome text', 'dws-plugin-template' ),
							default_value: '',
							// The sanitize seam: runs on every form submission before the value is stored.
							sanitize: static fn ( mixed $value ): string => \sanitize_text_field( (string) $value ),
							description: \__( 'Shown to administrators on the dashboard.', 'dws-plugin-template' ),
						),
						new SettingsField(
							id: 'items_per_page',
							type: 'number',
							label: \__( 'Items per page', 'dws-plugin-template' ),
							default_value: 10,
							sanitize: static fn ( mixed $value ): int => \max( 1, \min( 100, (int) $value ) ),
						),
						new SettingsField(
							id: 'color_scheme',
							type: 'select',
							label: \__( 'Color scheme', 'dws-plugin-template' ),
							default_value: 'light',
							options: array(
								'light' => \__( 'Light', 'dws-plugin-template' ),
								'dark'  => \__( 'Dark', 'dws-plugin-template' ),
							),
						),
					),
				),
			),
		);
	}

	// endregion
}
