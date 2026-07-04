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

// Persistent storage keys single-sourced in config/footprint.php, so the stores that create each row, the
// installer that removes them, and uninstall.php's no-build fallback can never desync.
/** @var array{installer_option: string, notices_option: string, settings_options: list<string>, dismissed_notices_meta: string} $footprint */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- inline @var type assertion, no description applies.
$footprint = require __DIR__ . '/footprint.php';

$installer_option       = $footprint['installer_option'];
$notices_option         = $footprint['notices_option'];
$dismissed_notices_meta = $footprint['dismissed_notices_meta'];
$settings_options       = $footprint['settings_options'];

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
		new OptionsStore( $installer_option, false ),
		array_merge( array( $notices_option ), $settings_options ),
		array( $dismissed_notices_meta ),
	),

	// One backend per page, bound to the empty WC_Settings_Page subclass WooCommerce recovers by class name.
	WooCommerceSettingsBackend::class => static fn (): WooCommerceSettingsBackend => new WooCommerceSettingsBackend( ExampleWCSettingsPage::class ),
);
