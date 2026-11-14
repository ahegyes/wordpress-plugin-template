<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Unit;

use DeepWebSolutions\PluginTemplate\Migrations;
use DeepWebSolutions\PluginTemplate\Options;
use DeepWebSolutions\PluginTemplate\Plugin;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class MigrationsTest extends TestCase {
	// The fingerprint of the key set each seeding step stores, in the order Migrations::get_steps() lists the steps; a release that adds settings appends its step and its fingerprint.
	protected const array SEEDED_KEY_SETS = array( 'f6bc160f5d6fc5db' );

	public function test_each_seeding_step_records_the_key_set_it_stores(): void {
		$seeding = \array_filter( Migrations::get_steps(), static fn ( mixed $step ): bool => $step instanceof \Closure && 'add_settings' === new \ReflectionFunction( $step )->getName() );

		// The fill-in renames the prefix, so each key counts by what follows it.
		$keys = \array_map( static fn ( string $key ): string => \str_starts_with( $key, Plugin::PREFIX . '_' ) ? \substr( $key, \strlen( Plugin::PREFIX ) + 1 ) : $key, \array_keys( Options::DEFAULTS ) );
		\sort( $keys );
		$fingerprint = \hash( 'xxh3', \implode( "\n", $keys ) );

		self::assertCount( \count( self::SEEDED_KEY_SETS ), $seeding, 'Each seeding step needs its fingerprint in SEEDED_KEY_SETS.' );
		self::assertSame( \array_last( self::SEEDED_KEY_SETS ), $fingerprint, "Options::DEFAULTS' keys changed: append self::add_settings( ... ) to Migrations::get_steps() and '$fingerprint' to SEEDED_KEY_SETS." );
	}
}
