<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration;

use DeepWebSolutions\PluginTemplate\Plugin;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class SafeguardRecipeTest extends TestCase {
	#[BeforeClass]
	public static function load_the_recipe(): void {
		$recipes = (string) \file_get_contents( \dirname( __DIR__, 2 ) . '/docs/recipes.md' );
		foreach ( array( 'function dws_tpl_is_relaxed(', 'final class SafeguardsTest ' ) as $marker ) {
			\preg_match( '/```php\n(<\?php declare\( strict_types=1 \);\n(?:(?!```).)*?' . \preg_quote( $marker, '/' ) . '(?:(?!```).)*)```/s', $recipes, $block );
			self::assertArrayHasKey( 1, $block, "docs/recipes.md holds no PHP file defining $marker." );

			$file = (string) \tempnam( \sys_get_temp_dir(), \str_replace( '_', '-', Plugin::PREFIX ) . '-safeguard-recipe-' );
			\file_put_contents( $file, $block[1] );
			require_once $file;
			\unlink( $file );
		}
	}

	#[AfterClass]
	public static function remove_the_recipes_safeguard(): void {
		remove_filter( 'map_meta_cap', 'dws_tpl_map_export_capability' );
	}

	// The recipe's test class is loaded from its block, so PHPUnit never discovers it and this row runs each of its rows.
	public function test_the_recipes_safeguard_passes_the_recipes_test(): void {
		foreach ( new \ReflectionClass( SafeguardsTest::class )->getMethods( \ReflectionMethod::IS_PUBLIC ) as $test ) {
			if ( ! \str_starts_with( $test->name, 'test_' ) ) {
				continue;
			}

			$provider = $test->getAttributes( DataProvider::class )[0] ?? null;
			$rows     = \is_null( $provider ) ? array( $test->name => array() ) : SafeguardsTest::{$provider->newInstance()->methodName()}();
			foreach ( $rows as $name => $row ) {
				$test->invoke( new SafeguardsTest( (string) $name ), ...$row );
			}
		}
	}
}
