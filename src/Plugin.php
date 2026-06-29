<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate;

use DeepWebSolutions\PluginTemplate\Feature\GenericFeature;
use DeepWebSolutions\PluginTemplate\Feature\WooCommerceFeature;
use DeepWebSolutions\PluginTemplate\Installer\Installer;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Installer\InstallerInterface;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\PluginInterface;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\PluginKernel;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\ValueObjects\PluginHeader;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Utilities\AdminNotices\AdminNoticeLogger;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Utilities\AdminNotices\AdminNoticesService;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\WooCommerce\Logging\WooCommerceLogger;
use DeepWebSolutions\PluginTemplate\Scoped\DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Plugin entry point — the consumer half of the framework's plugin contract.
 *
 * A per-plugin singleton holding the PHP-DI container and the registered Feature classes. Boot resolves
 * the kernel, which runs the installer, gates each Feature on its conditionals, and dispatches the
 * surviving components through the initialize then register_hooks lifecycle.
 *
 * @since   2.0.0
 * @version 2.0.0
 */
final class Plugin implements PluginInterface {
	// region FIELDS AND CONSTANTS

	/**
	 * Singleton instance.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @var     self|null
	 */
	private static ?self $instance = null;

	/**
	 * PSR-11 container resolving Features, components, conditionals, and the installer.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @var     ContainerInterface
	 */
	protected readonly ContainerInterface $container;

	/**
	 * Runtime engine, stored once boot() has run so repeat boots are no-ops.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @var     PluginKernel|null
	 */
	protected ?PluginKernel $kernel = null;

	/**
	 * Notice id the install-failure logger queues under.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @var     string
	 */
	protected const INSTALL_FAILURE_NOTICE = 'dws-plugin-template-install-failure';

	/**
	 * Name of the persistent, cross-request notice store the failure notice lives in. Public so the
	 * container registers the store under the same name the install-failure logger queues into; the logger
	 * constructor throws on an unregistered store name, so both sides must reference this one constant.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @var     string
	 */
	public const PERSISTENT_STORE = 'persistent';

	// endregion

	// region MAGIC METHODS

	/**
	 * Constructor.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	private function __construct() {
		// The container runs uncompiled: the demo component graph is small, and compiling autowiring needs a
		// writable, WP_DEBUG-gated cache directory a forkable template cannot assume is available on every host.
		$builder = new ContainerBuilder();
		$builder->addDefinitions( __DIR__ . '/../config/container.php' );

		$this->container = $builder->build();
	}

	// endregion

	// region GETTERS

	/**
	 * Returns the plugin's singleton instance, creating it on first call.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @return  self
	 */
	public static function get_instance(): self {
		return self::$instance ??= new self();
	}

	// endregion

	// region INHERITED METHODS

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	#[\Override]
	public function get_plugin_file(): string {
		return DWS_PLUGIN_TEMPLATE_FILE;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	#[\Override]
	public function get_plugin_header(): PluginHeader {
		return new PluginHeader( $this->get_plugin_file() );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	#[\Override]
	public function get_container(): ContainerInterface {
		return $this->container;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	#[\Override]
	public function get_feature_classes(): array {
		return array(
			GenericFeature::class,
			WooCommerceFeature::class,
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	#[\Override]
	public function get_installer(): InstallerInterface {
		/** @var InstallerInterface $installer */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- inline @var type assertion, no description applies.
		$installer = $this->container->get( Installer::class );

		return $installer;
	}

	// endregion

	// region METHODS

	/**
	 * Boots the plugin once. In an admin request it wires notice rendering before running the kernel — which
	 * stops the boot when the installer fails — so a queued failure notice still renders; the kernel then boots
	 * for every request.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @return  void
	 */
	public function boot(): void {
		if ( null !== $this->kernel ) {
			return;
		}

		// Notice rendering and the stale-notice cleanup only matter in the admin, where the notices render and
		// the dismiss AJAX fires; a front-end request skips both, sparing the wp_options read that remove_notice()
		// would cost. A failed install still queues into the persistent store, so the next admin request shows it.
		if ( is_admin() ) {
			$this->register_notice_rendering();

			// Drop any prior install-failure notice before the kernel runs; build_logger() re-queues it only
			// when this boot's installer routine fails again, so a recovered install stops surfacing a stale one.
			$this->notices()->remove_notice( self::INSTALL_FAILURE_NOTICE, self::PERSISTENT_STORE );
		}

		$this->kernel = PluginKernel::run( $this, $this->build_logger() );
	}

	// endregion

	// region HELPERS

	/**
	 * Wires the admin-notice service's render and dismissal hooks before the kernel boots, regardless of the
	 * installer outcome. A renderer dispatched as a Feature component would never register when the installer
	 * fails the boot, leaving a queued install-failure notice unrendered; wiring it here keeps that path live.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @return  void
	 */
	protected function register_notice_rendering(): void {
		$notices = $this->notices();

		add_action( 'admin_notices', array( $notices, 'render_notices' ) );

		$dismiss_action = $notices->get_dismiss_action();
		if ( null !== $dismiss_action ) {
			add_action( 'admin_footer', array( $notices, 'print_dismiss_script' ) );
			add_action( 'wp_ajax_' . $dismiss_action, array( $notices, 'handle_dismiss' ) );
		}
	}

	/**
	 * Builds the kernel's diagnostic logger, chosen at boot (plugins_loaded) when WooCommerce's presence is
	 * known: WooCommerce's logging stack when active, otherwise a persistent admin notice so a failed install
	 * or migration stays visible to administrators on a later request.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @return  LoggerInterface
	 */
	protected function build_logger(): LoggerInterface {
		if ( function_exists( 'wc_get_logger' ) ) {
			return new WooCommerceLogger( 'dws-plugin-template' );
		}

		return new AdminNoticeLogger(
			$this->notices(),
			self::INSTALL_FAILURE_NOTICE,
			self::PERSISTENT_STORE,
		);
	}

	/**
	 * Resolves the shared admin-notice service from the container.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @return  AdminNoticesService
	 */
	protected function notices(): AdminNoticesService {
		/** @var AdminNoticesService $service */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- inline @var type assertion, no description applies.
		$service = $this->container->get( AdminNoticesService::class );

		return $service;
	}

	// endregion
}
