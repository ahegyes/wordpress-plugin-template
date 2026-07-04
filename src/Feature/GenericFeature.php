<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Feature;

use DeepWebSolutions\PluginTemplate\Component\ExampleWPSettings;
use DeepWebSolutions\PluginTemplate\Component\WelcomeNotice;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Feature\FeatureInterface;

/**
 * Always-on Feature. Declares no conditionals, so it boots everywhere; carries the welcome notice and the
 * native WordPress settings example, proving the plugin runs without WooCommerce.
 *
 * @since   2.0.0
 * @version 2.0.0
 */
final class GenericFeature implements FeatureInterface {
	// region INHERITED METHODS

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	#[\Override]
	public static function get_conditional_classes(): array {
		return array();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	#[\Override]
	public function get_component_classes(): array {
		return array(
			WelcomeNotice::class,
			ExampleWPSettings::class,
		);
	}

	// endregion
}
