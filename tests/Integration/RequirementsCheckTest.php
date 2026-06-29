<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration;

use PHPUnit\Framework\TestCase;

use function DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Bootstrap\Plugin\get_plugin_metadata;
use function DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Bootstrap\Requirements\check_requirements;

final class RequirementsCheckTest extends TestCase {
	private string $basename = 'dws-plugin-template/dws-plugin-template.php';

	public function test_plugin_header_declares_expected_min_versions(): void {
		$meta = get_plugin_metadata( $this->basename );

		self::assertSame( '8.5', $meta['RequiresPHP'] );
		self::assertSame( '7.0', $meta['RequiresWP'] );
	}

	public function test_check_requirements_matches_runtime(): void {
		$result = check_requirements( $this->basename );

		$env_compatible = \is_php_version_compatible( '8.5' )
			&& \is_wp_version_compatible( '7.0' );

		if ( $env_compatible ) {
			self::assertTrue( $result );
			return;
		}

		self::assertInstanceOf( \WP_Error::class, $result );
		$codes = $result->get_error_codes();

		// Assert the code for whichever floor the runtime misses, so the below-floor CI entry (PHP at floor,
		// WordPress below it) proves specifically a WordPress incompatibility rather than any error at all.
		if ( ! \is_php_version_compatible( '8.5' ) ) {
			self::assertContains( 'plugin_php_incompatible', $codes );
		}
		if ( ! \is_wp_version_compatible( '7.0' ) ) {
			self::assertContains( 'plugin_wp_incompatible', $codes );
		}
	}
}
