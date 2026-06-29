<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Component;

use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Lifecycle\Hookable\HookableInterface;

/**
 * Demo component — renders an info notice in the WordPress admin to confirm the plugin booted and the
 * framework's Hookable dispatch works.
 *
 * @since   2.0.0
 * @version 2.0.0
 */
final class AdminNotice implements HookableInterface {
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
	 * Renders the admin notice to administrators. Hooked on `admin_notices`.
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

		printf(
			'<div class="notice notice-info"><p>%s</p></div>',
			esc_html__( 'DWS Plugin Template is active.', 'dws-plugin-template' )
		);
	}

	// endregion
}
