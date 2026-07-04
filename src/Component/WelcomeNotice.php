<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Component;

use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Lifecycle\Hookable\HookableInterface;

/**
 * Demo component — renders a welcome notice in the WordPress admin to confirm the plugin booted and the
 * framework's Hookable dispatch works. Named for its role so it never shadows the framework's AdminNotice
 * descriptor, which the same reader meets in the notice service.
 *
 * @since   2.0.0
 * @version 2.0.0
 */
final class WelcomeNotice implements HookableInterface {
	// region INHERITED METHODS

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	#[\Override]
	public function register_hooks(): void {
		add_action( 'admin_notices', array( $this, 'render' ) );
	}

	// endregion

	// region HOOKS

	/**
	 * Renders the welcome notice to administrators. Hooked on `admin_notices`.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @return  void
	 */
	public function render(): void {
		// admin_notices fires for every role that can reach the dashboard; gating the output to administrators
		// keeps plugin chrome away from lower-privileged users and models the capability check a real notice needs.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_admin_notice(
			esc_html__( 'DWS Plugin Template is active.', 'dws-plugin-template' ),
			array( 'type' => 'info' )
		);
	}

	// endregion
}
