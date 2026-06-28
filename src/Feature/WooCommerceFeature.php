<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Feature;

use DeepWebSolutions\PluginTemplate\Component\ExampleSettings;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Feature\FeatureInterface;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Utilities\Conditionals\Dependencies\WPPluginActiveConditional;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\WooCommerce\Conditionals\Dependencies\WooCommerceVersionConditional;

/**
 * WooCommerce-gated Feature. Its conditionals keep the whole Feature — and its components — out of the
 * boot unless WooCommerce is active and recent enough, so the components never load when WooCommerce is
 * absent.
 *
 * @since   2.0.0
 * @version 2.0.0
 */
final class WooCommerceFeature implements FeatureInterface {
	// region INHERITED METHODS

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	#[\Override]
	public static function get_conditional_classes(): array {
		return array(
			WPPluginActiveConditional::class,
			WooCommerceVersionConditional::class,
		);
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
			ExampleSettings::class,
		);
	}

	// endregion
}
