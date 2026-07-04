<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Unit;

use DeepWebSolutions\PluginTemplate\Component\WelcomeNotice;
use DeepWebSolutions\PluginTemplate\Plugin;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the plugin's main file end to end under the mock-WordPress harness: the requirements gate's
 * graceful degradation, the include-time lifecycle wiring, and the missing-build setup notice. Each include
 * runs in its own process because the entrypoint defines constants and functions a sibling test cannot undo.
 */
#[UsesClass( Plugin::class )]
final class EntrypointTest extends TestCase {
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_an_incompatible_runtime_renders_the_requirements_notice_and_suppresses_boot(): void {
		$this->prepare_mock_wp();
		// An unknown WordPress version reads as below the 7.0 floor, so the requirements gate must short-circuit.
		unset( $GLOBALS['wp_version'] );

		require $this->entrypoint_path();

		self::assertCount(
			1,
			WordPressStubState::$actions,
			'An incompatible runtime must register exactly the requirements notice and nothing else.'
		);
		self::assertSame( 'all_admin_notices', WordPressStubState::$actions[0]['hook'] );
		self::assertSame(
			array(),
			WordPressStubState::$activation_hooks,
			'Lifecycle wiring must not run when the requirements gate fails.'
		);
		self::assertSame( array(), WordPressStubState::$deactivation_hooks );
		self::assertNull(
			WordPressStubState::action_priority( 'plugins_loaded', 'dws_plugin_template_boot' ),
			'Boot must not be scheduled when the requirements gate fails.'
		);
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_a_compatible_runtime_wires_lifecycle_and_defers_boot_to_priority_15(): void {
		$this->prepare_mock_wp();
		$GLOBALS['wp_version'] = '7.0';

		require $this->entrypoint_path();

		self::assertCount(
			1,
			WordPressStubState::$activation_hooks,
			'The entrypoint must wire activation during the include, before any deferred boot.'
		);
		self::assertCount(
			1,
			WordPressStubState::$deactivation_hooks,
			'The entrypoint must wire deactivation during the include.'
		);
		self::assertSame(
			15,
			WordPressStubState::action_priority( 'plugins_loaded', 'dws_plugin_template_boot' ),
			'Boot must be deferred to plugins_loaded priority 15, after WooCommerce loads.'
		);
		self::assertFalse(
			WordPressStubState::has_object_action( 'admin_notices', WelcomeNotice::class, 'render' ),
			'The include must schedule boot, not run it: no component hook registers yet.'
		);
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_the_missing_build_notice_points_to_the_install_command(): void {
		$this->prepare_mock_wp();
		unset( $GLOBALS['wp_version'] );

		require $this->entrypoint_path();

		self::assertTrue(
			\function_exists( 'dws_plugin_template_render_setup_notice' ),
			'The entrypoint must define the missing-build notice renderer.'
		);

		\ob_start();
		dws_plugin_template_render_setup_notice();
		$output = (string) \ob_get_clean();

		self::assertStringContainsString(
			'composer packages-install',
			$output,
			'The setup notice must name the command that builds the dependencies.'
		);
		self::assertStringContainsString(
			'notice notice-error',
			$output,
			'The setup notice must render as a WordPress error notice.'
		);

		WordPressStubState::$user_can = false;
		\ob_start();
		dws_plugin_template_render_setup_notice();
		$gated_output = (string) \ob_get_clean();

		self::assertSame(
			'',
			$gated_output,
			'The setup notice must stay silent for users who cannot act on the build state.'
		);
	}

	public function test_the_requirements_gate_runs_before_the_full_autoloader(): void {
		$source = (string) \file_get_contents( $this->entrypoint_path() );

		$gate_position     = \strpos( $source, 'check_requirements(' );
		$autoload_position = \strpos( $source, 'require_once $dws_plugin_template_autoload' );

		self::assertIsInt( $gate_position, 'The entrypoint must call check_requirements().' );
		self::assertIsInt(
			$autoload_position,
			'The full autoloader must be required through the preflight-checked path variable.'
		);
		self::assertLessThan(
			$autoload_position,
			$gate_position,
			'The requirements gate must run before the full autoloader, so the scoped framework (PHP 8.5+ '
				. 'syntax) is never required on a runtime below the floor where it would fatal before the notice renders.'
		);
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_the_preflight_flags_a_missing_scoped_autoload(): void {
		$this->prepare_mock_wp();
		// An unknown WordPress version bails at the requirements gate, so the entrypoint defines its functions
		// (declared at file scope, so hoisted) without loading the full framework, leaving the predicate callable.
		unset( $GLOBALS['wp_version'] );

		require $this->entrypoint_path();

		$present = $this->entrypoint_path();
		self::assertFalse(
			\dws_plugin_template_dependencies_missing( $present, $present, $present ),
			'A complete build must pass the preflight.'
		);
		self::assertTrue(
			\dws_plugin_template_dependencies_missing( $present, '/does/not/exist/scoper-autoload.php', $present ),
			'A missing scoped autoload alone must trip the preflight, so a partial install fails closed on the '
				. 'setup notice rather than fataling on the require below.'
		);
	}

	protected function prepare_mock_wp(): void {
		require_once __DIR__ . '/bootstrap-wp-stubs.php';
		require_once __DIR__ . '/entrypoint-wp-stubs.php';

		if ( ! \defined( 'ABSPATH' ) ) {
			\define( 'ABSPATH', __DIR__ . '/' );
		}

		WordPressStubState::reset();
		( new \ReflectionProperty( Plugin::class, 'instance' ) )->setValue( null, null );
	}

	protected function entrypoint_path(): string {
		return __DIR__ . '/../../dws-plugin-template.php';
	}
}
