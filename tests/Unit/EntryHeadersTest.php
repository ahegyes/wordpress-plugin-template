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
		$entry       = (string) \file_get_contents( \dirname( __DIR__, 2 ) . '/' . Plugin::SLUG . '.php' );

		self::assertIsArray( $woocommerce );
		self::assertSame( 1, \preg_match( '/^\s*\*\s*WC tested up to:\s*(\S+)\s*$/m', $entry, $header ) );
		self::assertSame( \implode( '.', \array_slice( \explode( '.', $woocommerce['version'] ), 0, 2 ) ), $header[1] );
	}

	// The bundled updater offers each release as tested up to this header, since a plugin that ships through GitHub has no readme.txt.
	public function test_tested_up_to_names_the_locked_wordpress(): void {
		$lock      = \json_decode( (string) \file_get_contents( \dirname( __DIR__, 2 ) . '/composer.lock' ), true, 512, \JSON_THROW_ON_ERROR );
		$wordpress = \array_find( $lock['packages-dev'], static fn ( array $package ): bool => 'johnpbloch/wordpress-core' === $package['name'] );
		$entry     = (string) \file_get_contents( \dirname( __DIR__, 2 ) . '/' . Plugin::SLUG . '.php' );

		self::assertIsArray( $wordpress );
		self::assertSame( 1, \preg_match( '/^\s*\*\s*Tested up to:\s*(\S+)\s*$/m', $entry, $header ) );
		self::assertSame( \implode( '.', \array_slice( \explode( '.', $wordpress['version'] ), 0, 2 ) ), $header[1] );
	}

	public function test_every_block_carries_the_entry_version(): void {
		$entry  = (string) \file_get_contents( \dirname( __DIR__, 2 ) . '/' . Plugin::SLUG . '.php' );
		$blocks = \glob( \dirname( __DIR__, 2 ) . '/blocks/src/*/block.json' );

		self::assertSame( 1, \preg_match( '/^\s*\*\s*Version:\s*(\S+)\s*$/m', $entry, $header ) );
		self::assertIsArray( $blocks );
		foreach ( $blocks as $block ) {
			self::assertSame( $header[1], \json_decode( (string) \file_get_contents( $block ), true, 512, \JSON_THROW_ON_ERROR )['version'] ?? null, $block );
		}
	}
}
