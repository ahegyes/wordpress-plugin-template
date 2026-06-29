<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Unit;

use DeepWebSolutions\PluginTemplate\Component\AdminNotice;
use DeepWebSolutions\PluginTemplate\Component\ExampleSettings;
use DeepWebSolutions\PluginTemplate\Feature\GenericFeature;
use DeepWebSolutions\PluginTemplate\Feature\WooCommerceFeature;
use DeepWebSolutions\PluginTemplate\Installer\Installer;
use DeepWebSolutions\PluginTemplate\Plugin;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * Keeps docs/getting-started.md honest: every reference class it names must exist, so a rename that
 * forgets the docs fails here. The boot smoke ({@see PluginBootTest}) is the authoritative proof that
 * the documented plugin actually boots; this is the cheaper symbol-level guard beside it.
 *
 * @since   2.0.0
 * @version 2.0.0
 */
#[UsesClass( Plugin::class )]
#[UsesClass( GenericFeature::class )]
#[UsesClass( WooCommerceFeature::class )]
#[UsesClass( Installer::class )]
#[UsesClass( AdminNotice::class )]
#[UsesClass( ExampleSettings::class )]
final class GettingStartedTutorialTest extends TestCase {

	#[Test]
	public function classes_named_in_the_tutorial_exist(): void {
		$classes = array(
			Plugin::class,
			GenericFeature::class,
			WooCommerceFeature::class,
			Installer::class,
			AdminNotice::class,
			ExampleSettings::class,
		);
		foreach ( $classes as $class ) {
			self::assertTrue( \class_exists( $class ), "docs/getting-started.md names $class, which must exist." );
		}

		// ExampleWCSettingsPage extends the scoped \WC_Settings_Page, which is not loadable in a Unit run,
		// so assert the file the tutorial points at is present rather than autoloading the class.
		self::assertFileExists(
			__DIR__ . '/../../src/Settings/ExampleWCSettingsPage.php',
			'docs/getting-started.md names src/Settings/ExampleWCSettingsPage.php, which must exist.'
		);
	}
}
