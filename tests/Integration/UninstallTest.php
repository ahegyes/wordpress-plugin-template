<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration;

use DeepWebSolutions\PluginTemplate\Plugin;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class UninstallTest extends TestCase {
	protected const int USER = 1;

	#[RunInSeparateProcess]
	public function test_a_consenting_uninstall_removes_a_row_of_every_kind_the_framework_roots_store(): void {
		$prefix = Plugin::PREFIX . '_';
		update_option( DWS_TPL_OPTION_DELETE_DATA, 'yes' );
		update_option( "{$prefix}notices", array( 'seeded' ) );
		update_network_option( null, "{$prefix}network_notices", array( 'seeded' ) );
		set_transient( "{$prefix}boot_failure_seeded", 1, HOUR_IN_SECONDS );
		update_user_option( self::USER, "{$prefix}hidden_notices", array( 'seeded' ) );
		update_user_meta( self::USER, "{$prefix}network_hidden_notices", array( 'seeded' ) );
		wp_schedule_single_event( \time() + HOUR_IN_SECONDS, "{$prefix}revision_run" );
		as_schedule_single_action( \time() + HOUR_IN_SECONDS, "{$prefix}seeded", array(), Plugin::PREFIX );

		self::assertTrue( uninstall_plugin( plugin_basename( DWS_TPL_FILE ) ) );
		wp_cache_flush();

		self::assertFalse( get_option( DWS_TPL_OPTION_DELETE_DATA ) );
		self::assertFalse( get_option( "{$prefix}notices" ) );
		self::assertFalse( get_network_option( null, "{$prefix}network_notices" ) );
		self::assertFalse( get_transient( "{$prefix}boot_failure_seeded" ) );
		self::assertFalse( get_user_option( "{$prefix}hidden_notices", self::USER ) );
		self::assertSame( '', get_user_meta( self::USER, "{$prefix}network_hidden_notices", true ) );
		self::assertFalse( wp_next_scheduled( "{$prefix}revision_run" ) );
		self::assertSame(
			array(),
			as_get_scheduled_actions(
				array(
					'group'  => Plugin::PREFIX,
					'status' => 'pending',
				),
				'ids'
			)
		);

		// The other tests share the database, so the runner stores again what a new site starts with.
		dws_tpl_plugin()->migrations?->run();
		self::assertSame( 'no', get_option( DWS_TPL_OPTION_DELETE_DATA ) );
	}
}
