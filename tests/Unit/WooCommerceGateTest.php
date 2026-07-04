<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Unit;

use DeepWebSolutions\PluginTemplate\Component\ExampleSettings;
use DeepWebSolutions\PluginTemplate\Component\ExampleWPSettings;
use DeepWebSolutions\PluginTemplate\Component\WelcomeNotice;
use DeepWebSolutions\PluginTemplate\Feature\GenericFeature;
use DeepWebSolutions\PluginTemplate\Feature\WooCommerceFeature;
use DeepWebSolutions\PluginTemplate\Installer\Installer;
use DeepWebSolutions\PluginTemplate\Plugin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap-wp-stubs.php';

#[CoversClass( Plugin::class )]
#[UsesClass( GenericFeature::class )]
#[UsesClass( WooCommerceFeature::class )]
#[UsesClass( Installer::class )]
#[UsesClass( WelcomeNotice::class )]
#[UsesClass( ExampleSettings::class )]
#[UsesClass( ExampleWPSettings::class )]
final class WooCommerceGateTest extends TestCase {
	// A dedicated process: WC_VERSION is a constant a sibling test defines at 9.0.0 and cannot be redefined,
	// so the below-floor version is only assertable in isolation.
	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_the_woocommerce_feature_gates_out_when_woocommerce_is_below_the_minimum(): void {
		WordPressStubState::reset();
		( new \ReflectionProperty( Plugin::class, 'instance' ) )->setValue( null, null );

		WordPressStubState::$active_plugins = array( 'woocommerce/woocommerce.php' );
		if ( ! \defined( 'WC_VERSION' ) ) {
			\define( 'WC_VERSION', '7.5.0' );
		}

		Plugin::get_instance()->boot();

		self::assertFalse(
			WordPressStubState::has_object_action( 'init', ExampleSettings::class, 'register_settings_page' ),
			'A WooCommerce older than the configured 8.0 floor must gate the WooCommerce Feature out.'
		);
	}
}
