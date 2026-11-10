<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Unit;

use DeepWebSolutions\PluginTemplate\Plugin;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class EntryHeadersTest extends TestCase {
	public function test_wc_tested_up_to_names_the_locked_woocommerce(): void {
		$lock        = \json_decode( (string) \file_get_contents( \dirname( __DIR__, 2 ) . '/composer.lock' ), true, 512, \JSON_THROW_ON_ERROR );
		$woocommerce = \array_find( $lock['packages-dev'], static fn ( array $package ): bool => 'wp-plugin/woocommerce' === $package['name'] );

		self::assertIsArray( $woocommerce );
		self::assertSame( \implode( '.', \array_slice( \explode( '.', $woocommerce['version'] ), 0, 2 ) ), self::get_header( 'WC tested up to' ) );
	}

	public function test_phpstan_checks_wordpress_calls_at_the_entry_floor(): void {
		self::assertSame( 1, \preg_match( "/^\tWPCompat:\n\t\trequiresAtLeast: '([^']+)'$/m", (string) \file_get_contents( \dirname( __DIR__, 2 ) . '/phpstan.dist.neon' ), $floor ) );
		self::assertSame( self::get_header( 'Requires at least' ), $floor[1] );
	}

	protected static function get_header( string $name ): string {
		self::assertSame( 1, \preg_match( '/^\s*\*\s*' . \preg_quote( $name, '/' ) . ':\s*(\S+)\s*$/m', (string) \file_get_contents( \dirname( __DIR__, 2 ) . '/' . Plugin::SLUG . '.php' ), $header ) );
		return $header[1];
	}
}
