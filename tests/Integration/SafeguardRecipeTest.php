<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration;

use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class SafeguardRecipeTest extends TestCase {
	protected const string CAPABILITY = 'dws_tpl_export_data';

	protected const string FILTER = 'dws_tpl_restrict_data_export';

	#[BeforeClass]
	public static function load_the_recipe(): void {
		$recipes = \dirname( __DIR__, 2 ) . '/docs/recipes.md';
		self::assertFileExists( $recipes );
		\preg_match( '/```php\n(<\?php declare\( strict_types=1 \);\n(?:(?!```).)*?function dws_tpl_is_relaxed\((?:(?!```).)*)```/s', (string) \file_get_contents( $recipes ), $block );
		self::assertArrayHasKey( 1, $block, 'docs/recipes.md holds no includes/safeguards.php block defining dws_tpl_is_relaxed().' );

		$file = \sys_get_temp_dir() . '/dws-tpl-safeguard-recipe.php';
		\file_put_contents( $file, $block[1] );
		require_once $file;
	}

	public static function relaxations(): array {
		return array(
			'none'         => array( array(), false ),
			'null'         => array( array( 'relaxation' => null ), false ),
			'zero'         => array( array( 'relaxation' => 0 ), false ),
			'empty-string' => array( array( 'relaxation' => '' ), false ),
			'false'        => array( array( 'relaxation' => false ), true ),
		);
	}

	#[DataProvider( 'relaxations' )]
	public function test_only_an_explicit_false_relaxes_the_safeguard( array $spec, bool $relaxed ): void {
		$relaxation = static fn (): mixed => $spec['relaxation'];
		if ( \array_key_exists( 'relaxation', $spec ) ) {
			add_filter( self::FILTER, $relaxation );
		}

		try {
			self::assertSame( $relaxed ? array( 'manage_options' ) : array( 'do_not_allow' ), map_meta_cap( self::CAPABILITY, 0 ) );
		} finally {
			remove_filter( self::FILTER, $relaxation );
		}
	}

	public function test_an_explicit_false_does_not_relax_the_safeguard_before_the_plugin_booted(): void {
		global $wp_actions;
		self::assertGreaterThan( 0, did_action( 'dws_tpl_initialized' ) );
		$fired = $wp_actions['dws_tpl_initialized'];
		unset( $wp_actions['dws_tpl_initialized'] );
		add_filter( self::FILTER, '__return_false' );

		try {
			self::assertSame( array( 'do_not_allow' ), map_meta_cap( self::CAPABILITY, 0 ) );
		} finally {
			remove_filter( self::FILTER, '__return_false' );
			$wp_actions['dws_tpl_initialized'] = $fired;
		}
	}
}
