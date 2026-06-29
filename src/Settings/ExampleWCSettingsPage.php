<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Settings;

use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\WooCommerce\Backend\DescriptorBackedWCSettingsPage;

/**
 * Empty WooCommerce settings-page subclass. WooCommerce rebuilds settings-page objects each request and
 * recovers them by class name, so each page needs its own distinct class for the backend to bind its
 * descriptor to.
 *
 * @since   2.0.0
 * @version 2.0.0
 */
final class ExampleWCSettingsPage extends DescriptorBackedWCSettingsPage {}
