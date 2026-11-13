<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration;

use DeepWebSolutions\PluginTemplate\Greeting\GreetingFeature;
use DeepWebSolutions\PluginTemplate\Migrations;
use DeepWebSolutions\PluginTemplate\Plugin;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Migration\MigrationRunner;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\PluginIdentity;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\WooCommerce\Logging\WooCommerceLogger;
use DeepWebSolutions\PluginTemplate\Tests\Support\RunsRequestsTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class PluginBootTest extends TestCase {
	use RunsRequestsTrait;

	protected const string REVISION = Plugin::PREFIX . '_revision';

	public function test_the_plugin_boots_and_skips_the_greeting_while_its_gate_is_off(): void {
		$plugin = dws_tpl_plugin();

		self::assertSame( 'booted', $plugin->boundary->status );
		self::assertSame( GreetingFeature::should_load() ? array() : array( GreetingFeature::class ), $plugin->kernel?->skipped );
	}

	public function test_the_entry_loads_every_include_but_those_whose_names_start_with_an_underscore(): void {
		$includes = \dirname( DWS_TPL_FILE ) . '/includes/';

		self::assertContains( $includes . 'index.php', \get_included_files() );
		self::assertIsInt( \file_put_contents( $includes . '_skipped-include.php', "<?php\nconst DWS_TPL_SKIPPED_INCLUDE = true;\n" ) );
		try {
			$request = self::request( 'echo 0 < did_action( "dws_plugin_template/boot/succeeded" ) ? ( defined( "DWS_TPL_SKIPPED_INCLUDE" ) ? "loaded" : "skipped" ) : "not booted";' );
		} finally {
			\unlink( $includes . '_skipped-include.php' );
		}

		self::assertSame( 0, $request['status'], $request['output'] );
		self::assertSame( 'skipped', $request['last'], $request['output'] );
	}

	public function test_the_entry_loads_its_safeguards_before_the_bootstrap_package_and_its_floor_gate(): void {
		$root       = \dirname( DWS_TPL_FILE );
		$safeguards = \array_search( $root . '/Safeguards.php', \get_included_files(), true );
		$bootstrap  = \array_search( $root . '/vendor-prefixed/ahegyes/wp-framework-bootstrap/load.php', \get_included_files(), true );

		self::assertIsInt( $safeguards );
		self::assertIsInt( $bootstrap );
		self::assertLessThan( $bootstrap, $safeguards );
	}

	#[RunInSeparateProcess]
	public function test_the_debug_constant_logs_every_level(): void {
		\define( 'DWS_TPL_DEBUG', true );
		$logger = new \ReflectionProperty( Plugin::class, 'logger' )->getValue( new Plugin() );

		self::assertInstanceOf( WooCommerceLogger::class, $logger );
		self::assertSame( self::get_threshold( new WooCommerceLogger( new PluginIdentity( DWS_TPL_FILE, Plugin::SLUG, Plugin::PREFIX ), 'debug' ) ), self::get_threshold( $logger ) );
	}

	public function test_a_request_brings_the_stored_data_to_the_revision_of_the_code(): void {
		update_option( self::REVISION, 0 );
		try {
			$request = self::request( 'echo ' . MigrationRunner::class . '::get_revision( "' . self::REVISION . '" );' );
		} finally {
			wp_cache_flush(); // The request wrote the revision behind this process's cache.
			update_option( self::REVISION, \count( Migrations::get_steps() ) );
		}

		self::assertSame( 0, $request['status'], $request['output'] );
		self::assertSame( (string) \count( Migrations::get_steps() ), $request['last'] );
	}

	protected static function get_threshold( WooCommerceLogger $logger ): mixed {
		return new \ReflectionProperty( WooCommerceLogger::class, 'threshold' )->getValue( $logger );
	}
}
