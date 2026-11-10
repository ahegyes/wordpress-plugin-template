<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration;

use DeepWebSolutions\PluginTemplate\Plugin;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Uninstall\Uninstaller;
use DeepWebSolutions\PluginTemplate\Tests\Support\RunsRequestsTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class PartialDeployTest extends TestCase {
	use RunsRequestsTrait;

	public function test_the_build_notice_says_the_features_stay_off_until_the_plugin_is_reinstalled(): void {
		wp_set_current_user( 1 );
		\ob_start();
		try {
			dws_tpl_render_build_notice();
		} finally {
			$notice = (string) \ob_get_clean();
			wp_set_current_user( 0 );
		}

		$sentence = \Dom\HTMLDocument::createFromString( '<!DOCTYPE html><html><body>' . $notice . '</body></html>', \LIBXML_NOERROR )->querySelector( '.notice-error p' );

		self::assertSame( 'DWS Plugin Template is missing some of its files, so its features stay off until the plugin is reinstalled.', $sentence?->textContent ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- A DOM property.
	}

	public function test_a_missing_blocks_manifest_shows_the_build_notice_and_loads_nothing_built(): void {
		$manifest = \dirname( DWS_TPL_FILE ) . '/blocks/build/blocks-manifest.php';
		\rename( $manifest, "$manifest.hidden" );
		try {
			$request = self::request( 'echo wp_json_encode( array( function_exists( "dws_tpl_boot" ), false !== has_action( "all_admin_notices", "dws_tpl_render_build_notice" ) ) );' );
		} finally {
			\rename( "$manifest.hidden", $manifest );
		}

		self::assertSame( 0, $request['status'], $request['output'] );
		self::assertSame( '[false,true]', $request['last'] );
	}

	public function test_a_missing_assets_build_shows_the_build_notice_and_loads_nothing_built(): void {
		$asset = \dirname( DWS_TPL_FILE ) . '/assets/build/editor.asset.php';
		\rename( $asset, "$asset.hidden" );
		try {
			$request = self::request( 'echo wp_json_encode( array( function_exists( "dws_tpl_boot" ), false !== has_action( "all_admin_notices", "dws_tpl_render_build_notice" ) ) );' );
		} finally {
			\rename( "$asset.hidden", $asset );
		}

		self::assertSame( 0, $request['status'], $request['output'] );
		self::assertSame( '[false,true]', $request['last'] );
	}

	public function test_a_missing_bootstrap_package_shows_the_build_notice_and_loads_nothing_built(): void {
		$bootstrap = \dirname( DWS_TPL_FILE ) . '/vendor-prefixed/ahegyes/wp-framework-bootstrap/load.php';
		\rename( $bootstrap, "$bootstrap.hidden" );
		try {
			$request = self::request( 'echo wp_json_encode( array( function_exists( "dws_tpl_boot" ), false !== has_action( "all_admin_notices", "dws_tpl_render_build_notice" ) ) );' );
		} finally {
			\rename( "$bootstrap.hidden", $bootstrap );
		}

		self::assertSame( 0, $request['status'], $request['output'] );
		self::assertSame( '[false,true]', $request['last'] );
	}

	public function test_an_include_that_throws_shows_the_build_notice_and_boots_nothing(): void {
		$include = \dirname( DWS_TPL_FILE ) . '/includes/zz-throws.php';
		\file_put_contents( $include, "<?php\nthrow new RuntimeException( 'A partial deploy.' );\n" );
		try {
			$request = self::request( 'echo wp_json_encode( array( false !== has_action( "plugins_loaded", "dws_tpl_boot" ), false !== has_action( "all_admin_notices", "dws_tpl_render_build_notice" ) ) );' );
		} finally {
			\unlink( $include );
		}

		self::assertSame( 0, $request['status'], $request['output'] );
		self::assertSame( '[false,true]', $request['last'] );
	}

	public function test_a_build_error_is_logged_with_its_location_once_an_hour(): void {
		$message = 'A partial deploy ' . \uniqid() . '.';
		$include = \dirname( DWS_TPL_FILE ) . '/includes/zz-throws.php';
		\file_put_contents( $include, "<?php\nthrow new RuntimeException( '$message' );\n" );
		try {
			$first  = self::request( 'echo 1;' );
			$second = self::request( 'echo 1;' );
		} finally {
			\unlink( $include );
			delete_transient( Plugin::PREFIX . '_build_error_' . \md5( $message ) );
		}

		self::assertStringContainsString( "did not finish loading: $message in $include:2", $first['output'] );
		self::assertStringNotContainsString( 'did not finish loading', $second['output'] );
	}

	public function test_the_peer_getters_a_partial_deploy_defines_still_answer(): void {
		$root = \dirname( DWS_TPL_FILE ) . '/src/Plugin.php'; // options.php reads it at include time, after metadata.php has loaded.
		\rename( $root, "$root.hidden" );
		try {
			$request = self::request( 'echo wp_json_encode( array( false !== has_action( "plugins_loaded", "dws_tpl_boot" ), dws_tpl_get_plugin_slug(), dws_tpl_get_plugin_name(), dws_tpl_get_plugin_version() ) );' );
		} finally {
			\rename( "$root.hidden", $root );
		}

		self::assertSame( 0, $request['status'], $request['output'] );
		self::assertSame( wp_json_encode( array( false, dws_tpl_get_plugin_slug(), dws_tpl_get_plugin_name(), dws_tpl_get_plugin_version() ) ), $request['last'] );
	}

	public function test_a_deactivation_whose_composition_root_cannot_be_built_is_reported_as_a_build_error(): void {
		$root = \dirname( DWS_TPL_FILE ) . '/vendor-prefixed/ahegyes/wp-framework-core/src/PluginBoot.php'; // The composition root constructs it, and no include reads it.
		\rename( $root, "$root.hidden" );
		try {
			$request = self::request( 'remove_action( "all_admin_notices", "dws_tpl_render_build_notice" ); do_action( "deactivate_" . plugin_basename( DWS_TPL_FILE ), false ); echo false !== has_action( "all_admin_notices", "dws_tpl_render_build_notice" ) ? "reported" : "silent";' );
		} finally {
			\rename( "$root.hidden", $root );
		}

		self::assertSame( 0, $request['status'], $request['output'] );
		self::assertSame( 'reported', $request['last'] );
	}

	#[RunInSeparateProcess]
	public function test_an_uninstall_whose_engine_is_missing_keeps_the_data_and_lets_the_web_delete_the_files(): void {
		update_option( DWS_TPL_OPTION_DELETE_DATA, 'yes' );
		$log = (string) \tempnam( \sys_get_temp_dir(), 'dws-tpl-uninstall-' );
		\ini_set( 'error_log', $log ); // phpcs:ignore WordPress.PHP.IniSet.Risky -- The child process's stderr would fail the test.
		\spl_autoload_register(
			static function ( string $class_name ): void {
				if ( Uninstaller::class === $class_name ) {
					throw new \Error( "Failed opening required '$class_name'." ); // Stands in for an engine that cannot load.
				}
			},
			true,
			true
		);

		self::assertTrue( uninstall_plugin( plugin_basename( DWS_TPL_FILE ) ) );
		self::assertSame( 'yes', get_option( DWS_TPL_OPTION_DELETE_DATA ) );
		self::assertStringContainsString( 'kept its data on uninstall', (string) \file_get_contents( $log ) );

		\unlink( $log );
		update_option( DWS_TPL_OPTION_DELETE_DATA, 'no' );
	}
}
