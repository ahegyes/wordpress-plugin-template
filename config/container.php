<?php declare( strict_types=1 );

/**
 * PHP-DI container definitions.
 *
 * PHP-DI autowires constructor types, so the Features and most components need no entry here. Only the
 * classes whose constructor takes a scalar, a value object, or a chosen identifier are declared — the
 * conditionals the kernel gates Features on, the installer's option store, the notice service, and the
 * WooCommerce settings backend bound to its page subclass.
 *
 * @since   2.0.0
 * @version 2.0.0
 *
 * @package DeepWebSolutions\PluginTemplate
 */

use DeepWebSolutions\PluginTemplate\Installer\Installer;
use DeepWebSolutions\PluginTemplate\Plugin;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Shared\Version\Version;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Storage\MemoryStore;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Storage\OptionsStore;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Storage\UserMetaStore;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Utilities\AdminNotices\AdminNoticesService;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Utilities\AdminNotices\DismissedNoticesTracker;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Utilities\AdminNotices\NoticeStore;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Utilities\Conditionals\Dependencies\WPPluginActiveConditional;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\WooCommerce\Backend\WooCommerceSettingsBackend;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\WooCommerce\Conditionals\Dependencies\WooCommerceVersionConditional;
use DeepWebSolutions\PluginTemplate\Settings\ExampleWCSettingsPage;

// Persistent storage keys named once here, so the stores that create each row and the installer that
// removes it on uninstall stay in sync. The WooCommerce settings fields each persist one wp_options row as
// {slug}_{field}; keep this list aligned with the ExampleSettings descriptor.
$notices_option         = 'dws_plugin_template_notices';
$dismissed_notices_meta = 'dws_plugin_template_dismissed_notices';
$settings_options       = array( 'dws_plugin_template_enable_feature', 'dws_plugin_template_api_key' );

return array(

	// The WooCommerce Feature gates on these; the kernel resolves each from the container before building the Feature.
	WPPluginActiveConditional::class     => static fn (): WPPluginActiveConditional => new WPPluginActiveConditional( 'woocommerce/woocommerce.php' ),
	WooCommerceVersionConditional::class => static fn (): WooCommerceVersionConditional => new WooCommerceVersionConditional( Version::from_string( '8.0' ) ),

	// Two stores: a per-request memory store and an autoload-off wp_options persistent store. The install-failure
	// logger queues into the persistent store, under Plugin::PERSISTENT_STORE, so the notice survives the request
	// that stops the boot; the store name is shared with Plugin so the logger and the registration cannot desync.
	AdminNoticesService::class => static fn (): AdminNoticesService => new AdminNoticesService(
		array(
			AdminNoticesService::DEFAULT_STORE => new NoticeStore( new MemoryStore() ),
			Plugin::PERSISTENT_STORE           => new NoticeStore( new OptionsStore( $notices_option, false ) ),
		),
		new DismissedNoticesTracker( new UserMetaStore( $dismissed_notices_meta ) ),
		'dws_plugin_template_dismiss_notice',
	),

	// The installer owns one autoload-off wp_options row (stored version + install marker) and is handed the
	// full uninstall footprint — the persistent notice option, the settings options, and the dismissal meta.
	Installer::class => static fn (): Installer => new Installer(
		new OptionsStore( Installer::STORE_KEY, false ),
		array_merge( array( $notices_option ), $settings_options ),
		array( $dismissed_notices_meta ),
	),

	// One backend per page, bound to the empty WC_Settings_Page subclass WooCommerce recovers by class name.
	WooCommerceSettingsBackend::class => static fn (): WooCommerceSettingsBackend => new WooCommerceSettingsBackend( ExampleWCSettingsPage::class ),
);
