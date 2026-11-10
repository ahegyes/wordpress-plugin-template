<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration;

use DeepWebSolutions\PluginTemplate\Feature\Feature;
use DeepWebSolutions\PluginTemplate\Plugin;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Migration\MigrationRunner;
use DeepWebSolutions\PluginTemplate\Tests\Support\RunsRequestsTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class PluginBootTest extends TestCase {
	use RunsRequestsTrait;

	public function test_the_plugin_boots_with_the_greeting_gated_off_until_a_site_switches_it_on(): void {
		$plugin = dws_tpl_plugin();

		self::assertSame( 'booted', $plugin->boot->status );
		self::assertSame( array( Feature::class ), $plugin->kernel?->skipped );
	}

	public function test_the_entry_loads_every_include_but_those_whose_names_start_with_an_underscore(): void {
		$includes = \dirname( DWS_TPL_FILE ) . '/includes/';

		self::assertContains( $includes . 'index.php', \get_included_files() );
		self::assertIsInt( \file_put_contents( $includes . '_skipped-include.php', "<?php\nconst DWS_TPL_SKIPPED_INCLUDE = true;\n" ) );
		try {
			$request = self::request( 'echo 0 < did_action( "dws_tpl/boot/succeeded" ) ? ( defined( "DWS_TPL_SKIPPED_INCLUDE" ) ? "loaded" : "skipped" ) : "not booted";' );
		} finally {
			\unlink( $includes . '_skipped-include.php' );
		}

		self::assertSame( 0, $request['status'], $request['output'] );
		self::assertSame( 'skipped', $request['last'], $request['output'] );
	}

	public function test_the_entry_loads_its_safeguards_before_the_bootstrap_package_and_its_floor_gate(): void {
		$root       = \dirname( DWS_TPL_FILE );
		$safeguards = \array_search( $root . '/includes/safeguards.php', \get_included_files(), true );
		$bootstrap  = \array_search( $root . '/vendor-prefixed/ahegyes/wp-framework-bootstrap/load.php', \get_included_files(), true );

		self::assertIsInt( $safeguards );
		self::assertIsInt( $bootstrap );
		self::assertLessThan( $bootstrap, $safeguards );
	}

	public function test_the_stored_data_is_at_the_revision_of_the_code(): void {
		self::assertInstanceOf( MigrationRunner::class, dws_tpl_plugin()->migrations );
		self::assertSame( \count( dws_tpl_get_migration_steps() ), MigrationRunner::get_revision( Plugin::PREFIX . '_revision' ) );
	}
}
