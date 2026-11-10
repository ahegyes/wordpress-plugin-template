<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration;

use DeepWebSolutions\PluginTemplate\Blocks;
use DeepWebSolutions\PluginTemplate\Plugin;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class BlocksTest extends TestCase {
	#[After]
	protected function dequeue(): void {
		wp_dequeue_script( Plugin::SLUG . '-editor' );
		wp_deregister_script( Plugin::SLUG . '-editor' );
	}

	public function test_the_example_block_registers_from_the_build_with_its_scripts_and_styles(): void {
		$block = \WP_Block_Type_Registry::get_instance()->get_registered( 'dws-plugin-template/example-notice' );

		self::assertInstanceOf( \WP_Block_Type::class, $block );
		self::assertSame( array( 'dws-plugin-template-example-notice-editor-script' ), $block->editor_script_handles );
		self::assertSame( array( 'dws-plugin-template-example-notice-editor-style' ), $block->editor_style_handles );
		self::assertSame( array( 'dws-plugin-template-example-notice-style' ), $block->style_handles );
		self::assertSame(
			array(
				array(
					'name'  => 'outlined',
					'label' => 'Outlined',
				),
			),
			$block->styles
		);
	}

	public function test_the_example_block_renders_its_text_in_the_visitors_language_with_its_style(): void {
		$translate = static fn ( string $translation, string $text, string $domain ): string => 'dws-plugin-template' === $domain && 'An example notice from DWS Plugin Template.' === $text ? 'Ein Beispielhinweis.' : $translation;
		add_filter( 'gettext', $translate, 10, 3 );
		try {
			$html = do_blocks( '<!-- wp:dws-plugin-template/example-notice {"className":"is-style-outlined"} /-->' );
		} finally {
			remove_filter( 'gettext', $translate );
		}

		$notice = \Dom\HTMLDocument::createFromString( '<!DOCTYPE html><html><body>' . $html . '</body></html>', \LIBXML_NOERROR )->querySelector( 'p' );

		self::assertNotNull( $notice );
		self::assertEqualsCanonicalizing( array( 'wp-block-dws-plugin-template-example-notice', 'is-style-outlined' ), \explode( ' ', (string) $notice->getAttribute( 'class' ) ) );
		self::assertSame( 'Ein Beispielhinweis.', \trim( (string) $notice->textContent ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- A DOM property.
	}

	public function test_the_block_editor_gets_the_editor_script_with_its_generated_dependencies(): void {
		$blocks = new Blocks();
		$blocks->register_hooks();
		self::assertSame( 10, has_action( 'enqueue_block_editor_assets', array( $blocks, 'enqueue_editor_script' ) ) );
		remove_action( 'init', array( $blocks, 'register_blocks' ) );
		remove_action( 'enqueue_block_editor_assets', array( $blocks, 'enqueue_editor_script' ) );

		$blocks->enqueue_editor_script();

		$asset = require plugin_dir_path( DWS_TPL_FILE ) . 'assets/build/editor.asset.php';
		self::assertTrue( wp_script_is( Plugin::SLUG . '-editor', 'enqueued' ) );
		self::assertSame( $asset['dependencies'], wp_scripts()->registered[ Plugin::SLUG . '-editor' ]->deps );
		self::assertSame( $asset['version'], wp_scripts()->registered[ Plugin::SLUG . '-editor' ]->ver );
	}
}
