<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Unit;

use DeepWebSolutions\PluginTemplate\Installer\Installer;
use DeepWebSolutions\PluginTemplate\Plugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap-wp-stubs.php';

#[CoversClass( Installer::class )]
#[UsesClass( Plugin::class )]
final class UninstallFallbackTest extends TestCase {
	protected function setUp(): void {
		WordPressStubState::reset();
		( new \ReflectionProperty( Plugin::class, 'instance' ) )->setValue( null, null );
	}

	// A dedicated process: the fallback path requires defining WP_UNINSTALL_PLUGIN, which no sibling test
	// can undo.
	// phpcs:disable WordPress.WP.AlternativeFunctions -- the Unit suite runs without WordPress, so WP_Filesystem / wp_delete_file do not exist; plain PHP filesystem calls on the temp copy are the only option.
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_the_no_build_fallback_deletes_every_footprint_key(): void {
		$footprint = require __DIR__ . '/../../config/footprint.php';

		// A tree carrying only uninstall.php + config/footprint.php simulates the unbuilt install: no
		// vendor/, no dependencies/, so the preflight must take the direct-delete fallback.
		$plugin_dir = \sys_get_temp_dir() . '/dws-uninstall-fallback-' . \uniqid();
		self::assertTrue( \mkdir( $plugin_dir . '/config', 0777, true ) );
		\copy( __DIR__ . '/../../uninstall.php', $plugin_dir . '/uninstall.php' );
		\copy( __DIR__ . '/../../config/footprint.php', $plugin_dir . '/config/footprint.php' );

		foreach ( $footprint['options'] as $option ) {
			WordPressStubState::$options[ $option ] = 'seeded';
		}
		WordPressStubState::$options['unrelated_plugin_option'] = 'keep me';

		\define( 'WP_UNINSTALL_PLUGIN', true );
		try {
			require $plugin_dir . '/uninstall.php';
		} finally {
			\unlink( $plugin_dir . '/uninstall.php' );
			\unlink( $plugin_dir . '/config/footprint.php' );
			\rmdir( $plugin_dir . '/config' );
			\rmdir( $plugin_dir );
		}

		foreach ( $footprint['options'] as $option ) {
			self::assertArrayNotHasKey(
				$option,
				WordPressStubState::$options,
				"The no-build fallback must delete the $option option row."
			);
		}
		self::assertArrayHasKey(
			'unrelated_plugin_option',
			WordPressStubState::$options,
			'The no-build fallback must delete only the plugin footprint, leaving foreign options intact.'
		);
		foreach ( $footprint['user_meta'] as $meta_key ) {
			self::assertContains(
				$meta_key,
				WordPressStubState::$deleted_user_meta,
				"The no-build fallback must delete the $meta_key user-meta key."
			);
		}
	}
	// phpcs:disable WordPress.WP.AlternativeFunctions -- same no-WordPress constraint as the fallback test above.
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_a_throwing_container_path_still_deletes_the_footprint(): void {
		$footprint = require __DIR__ . '/../../config/footprint.php';

		// All three preflight artifacts exist, but the autoloader throws — the broken-build state the
		// preflight cannot see. The catch must route to the same framework-free deletes.
		$plugin_dir = \sys_get_temp_dir() . '/dws-uninstall-throwing-' . \uniqid();
		self::assertTrue( \mkdir( $plugin_dir . '/config', 0777, true ) );
		self::assertTrue( \mkdir( $plugin_dir . '/vendor', 0777, true ) );
		self::assertTrue( \mkdir( $plugin_dir . '/dependencies/ahegyes/wp-framework-bootstrap', 0777, true ) );
		\copy( __DIR__ . '/../../uninstall.php', $plugin_dir . '/uninstall.php' );
		\copy( __DIR__ . '/../../config/footprint.php', $plugin_dir . '/config/footprint.php' );
		\file_put_contents( $plugin_dir . '/vendor/autoload.php', '<?php throw new \RuntimeException( "corrupt autoloader" );' );
		\file_put_contents( $plugin_dir . '/dependencies/scoper-autoload.php', '<?php' );
		\file_put_contents( $plugin_dir . '/dependencies/ahegyes/wp-framework-bootstrap/functions.php', '<?php' );

		foreach ( $footprint['options'] as $option ) {
			WordPressStubState::$options[ $option ] = 'seeded';
		}

		\define( 'WP_UNINSTALL_PLUGIN', true );
		try {
			require $plugin_dir . '/uninstall.php';
		} finally {
			\unlink( $plugin_dir . '/uninstall.php' );
			\unlink( $plugin_dir . '/config/footprint.php' );
			\unlink( $plugin_dir . '/vendor/autoload.php' );
			\unlink( $plugin_dir . '/dependencies/scoper-autoload.php' );
			\unlink( $plugin_dir . '/dependencies/ahegyes/wp-framework-bootstrap/functions.php' );
			\rmdir( $plugin_dir . '/dependencies/ahegyes/wp-framework-bootstrap' );
			\rmdir( $plugin_dir . '/dependencies/ahegyes' );
			\rmdir( $plugin_dir . '/dependencies' );
			\rmdir( $plugin_dir . '/vendor' );
			\rmdir( $plugin_dir . '/config' );
			\rmdir( $plugin_dir );
		}

		foreach ( $footprint['options'] as $option ) {
			self::assertArrayNotHasKey(
				$option,
				WordPressStubState::$options,
				"A throwing container path must still delete the $option option row."
			);
		}
		foreach ( $footprint['user_meta'] as $meta_key ) {
			self::assertContains(
				$meta_key,
				WordPressStubState::$deleted_user_meta,
				"A throwing container path must still delete the $meta_key user-meta key."
			);
		}
	}
	// phpcs:enable WordPress.WP.AlternativeFunctions

	public function test_the_container_wired_footprint_matches_the_footprint_file(): void {
		$footprint = require __DIR__ . '/../../config/footprint.php';

		$installer = Plugin::get_instance()->get_container()->get( Installer::class );
		self::assertInstanceOf( Installer::class, $installer );

		$wired_options   = ( new \ReflectionProperty( Installer::class, 'footprint_options' ) )->getValue( $installer );
		$wired_user_meta = ( new \ReflectionProperty( Installer::class, 'footprint_user_meta' ) )->getValue( $installer );
		$store           = ( new \ReflectionProperty( Installer::class, 'store' ) )->getValue( $installer );
		$store_key       = ( new \ReflectionProperty( $store, 'option_key' ) )->getValue( $store );

		self::assertIsArray( $wired_options );
		self::assertIsArray( $wired_user_meta );
		self::assertIsString( $store_key );

		// The installer clears its own store row plus the handed option list; together they must equal the
		// footprint file's flat list, or the live uninstall and the no-build fallback have drifted apart.
		$live_options     = array( $store_key, ...$wired_options );
		$expected_options = $footprint['options'];
		\sort( $live_options );
		\sort( $expected_options );
		self::assertSame( $expected_options, $live_options, 'config/container.php must wire the exact option footprint config/footprint.php declares.' );

		$live_user_meta     = array( ...$wired_user_meta );
		$expected_user_meta = $footprint['user_meta'];
		\sort( $live_user_meta );
		\sort( $expected_user_meta );
		self::assertSame( $expected_user_meta, $live_user_meta, 'config/container.php must wire the exact user-meta footprint config/footprint.php declares.' );
	}
}
