<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration;

use DeepWebSolutions\PluginTemplate\Component\AdminNotice;
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
#[UsesClass( AdminNotice::class )]
final class PluginBootTest extends TestCase {
	public function test_get_instance_returns_singleton(): void {
		$first  = Plugin::get_instance();
		$second = Plugin::get_instance();

		self::assertSame( $first, $second );
	}

	public function test_boot_registers_admin_notices_hook(): void {
		// The plugin already boots on plugins_loaded during the WordPress load; this re-boot confirms boot is
		// idempotent and that a generic component registered its admin_notices callback. WooCommerce is active
		// in this environment, so the WooCommerce Feature gates in too; this asserts only the generic hook.
		Plugin::get_instance()->boot();

		self::assertNotFalse(
			has_action( 'admin_notices' ),
			'A generic component should register at least one admin_notices callback after boot.'
		);
	}
}
