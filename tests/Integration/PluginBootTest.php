<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration;

use DeepWebSolutions\PluginTemplate\Component\WelcomeNotice;
use DeepWebSolutions\PluginTemplate\Feature\GenericFeature;
use DeepWebSolutions\PluginTemplate\Feature\WooCommerceFeature;
use DeepWebSolutions\PluginTemplate\Installer\Installer;
use DeepWebSolutions\PluginTemplate\Plugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass( Plugin::class )]
#[UsesClass( GenericFeature::class )]
#[UsesClass( WooCommerceFeature::class )]
#[UsesClass( Installer::class )]
#[UsesClass( WelcomeNotice::class )]
final class PluginBootTest extends TestCase {
	public function test_get_instance_returns_singleton(): void {
		$first  = Plugin::get_instance();
		$second = Plugin::get_instance();

		self::assertSame( $first, $second );
	}

	public function test_boot_registers_the_generic_admin_notice_callback(): void {
		// The plugin boots on plugins_loaded during the WordPress load. Assert GenericFeature dispatched its own
		// WelcomeNotice::render onto admin_notices, not merely that some callback exists: WordPress core, the active
		// WooCommerce, and the pre-kernel notice renderer all register admin_notices handlers, so a bare
		// has_action() stays green even when component dispatch is broken. The container shares the resolved
		// component, so this is the instance the kernel hooked.
		Plugin::get_instance()->boot();

		$notice = Plugin::get_instance()->get_container()->get( WelcomeNotice::class );
		self::assertInstanceOf( WelcomeNotice::class, $notice );

		self::assertNotFalse(
			has_action( 'admin_notices', array( $notice, 'render' ) ),
			'GenericFeature must register the WelcomeNotice render callback.'
		);
	}
}
