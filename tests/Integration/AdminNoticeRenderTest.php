<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration;

use DeepWebSolutions\PluginTemplate\Component\AdminNotice;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass( AdminNotice::class )]
final class AdminNoticeRenderTest extends TestCase {
	public function test_render_outputs_info_notice_with_expected_text(): void {
		// render() gates on manage_options; the default WP install's first user carries it.
		\wp_set_current_user( 1 );

		\ob_start();
		( new AdminNotice() )->render();
		$output = (string) \ob_get_clean();

		self::assertStringContainsString( 'class="notice notice-info"', $output );
		self::assertStringContainsString( 'DWS Plugin Template is active.', $output );
	}
}
