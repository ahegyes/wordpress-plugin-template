<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration;

use DeepWebSolutions\PluginTemplate\Migrations;
use DeepWebSolutions\PluginTemplate\Options;
use DeepWebSolutions\PluginTemplate\Plugin;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Migration\MigrationRunner;
use DeepWebSolutions\PluginTemplate\Tests\Support\RestoresOptionsTrait;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class MigrationsTest extends TestCase {
	use RestoresOptionsTrait;

	protected const string REVISION = Plugin::PREFIX . '_revision';

	protected const array OPTIONS = array( Options::GREETING_ENABLED, Options::GREETING, Options::GREETING_ORDERS, Options::DELETE_DATA );

	#[After]
	protected function restore_the_revision(): void {
		update_option( self::REVISION, \count( Migrations::get_steps() ) );
	}

	// The runner's time budget lasts for its request, and this process's bootstrap spends it when it migrates a new site, so the row runs as a request of its own.
	#[RunInSeparateProcess]
	public function test_the_runner_brings_a_new_site_to_revision_one_with_the_untranslated_defaults_autoloaded(): void {
		global $wpdb;
		foreach ( self::OPTIONS as $key ) {
			delete_option( $key );
		}
		update_option( self::REVISION, 0 );

		dws_tpl_plugin()->migrations?->run();

		self::assertSame( 1, MigrationRunner::get_revision( self::REVISION ) );
		self::assertSame( 'no', get_option( Options::GREETING_ENABLED ) );
		self::assertSame( '1', get_option( Options::GREETING_ORDERS ) );
		self::assertSame( 'no', get_option( Options::DELETE_DATA ) );
		self::assertFalse( get_option( Options::GREETING ) );
		foreach ( array( Options::GREETING_ENABLED, Options::GREETING_ORDERS, Options::DELETE_DATA ) as $key ) {
			self::assertSame( 'on', $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", $key ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Reads the stored autoload flag.
		}
	}

	public function test_the_first_step_keeps_the_values_a_site_stored(): void {
		update_option( Options::GREETING_ENABLED, 'yes' );
		update_option( Options::GREETING_ORDERS, '5' );

		Migrations::get_steps()[0]();

		self::assertSame( 'yes', get_option( Options::GREETING_ENABLED ) );
		self::assertSame( '5', get_option( Options::GREETING_ORDERS ) );
	}

	public function test_the_first_step_throws_when_a_default_cannot_be_stored(): void {
		global $wpdb;
		delete_option( Options::DELETE_DATA );
		$fail       = static fn ( string $query ): string => \str_contains( $query, "'" . Options::DELETE_DATA . "'" ) && \str_starts_with( \ltrim( $query ), 'INSERT' ) ? "SELECT * FROM {$wpdb->prefix}dws_tpl_missing" : $query;
		$suppressed = $wpdb->suppress_errors();
		add_filter( 'query', $fail );

		try {
			$this->expectExceptionObject( new \RuntimeException( 'The option ' . Options::DELETE_DATA . ' could not be stored.' ) );
			Migrations::get_steps()[0]();
		} finally {
			remove_filter( 'query', $fail );
			$wpdb->suppress_errors( $suppressed );
		}
	}
}
