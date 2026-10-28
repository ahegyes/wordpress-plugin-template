<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration;

use DeepWebSolutions\PluginTemplate\Migrations;
use DeepWebSolutions\PluginTemplate\Plugin;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\AdminNotice\AdminNotices;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Migration\MigrationRunner;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Scheduling\Scheduler;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class PluginBootTest extends TestCase {
	public function test_the_plugin_boots_with_the_scheduler_and_admin_notices_as_its_only_roots(): void {
		$plugin = dws_tpl_plugin();

		self::assertSame( 'booted', $plugin->boot->status );
		self::assertSame( array( Scheduler::class, AdminNotices::class ), $plugin->kernel?->resolved );
		self::assertSame( array(), $plugin->kernel->skipped );
	}

	public function test_the_stored_data_is_at_the_revision_of_the_code(): void {
		self::assertInstanceOf( MigrationRunner::class, dws_tpl_plugin()->migrations );
		self::assertSame( \count( Migrations::STEPS ), MigrationRunner::get_revision( Plugin::PREFIX . '_revision' ) );
	}
}
