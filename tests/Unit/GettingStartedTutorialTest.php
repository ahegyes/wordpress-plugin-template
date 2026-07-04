<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Unit;

use DeepWebSolutions\PluginTemplate\Component\ExampleSettings;
use DeepWebSolutions\PluginTemplate\Component\ExampleWPSettings;
use DeepWebSolutions\PluginTemplate\Component\WelcomeNotice;
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
#[UsesClass( WelcomeNotice::class )]
#[UsesClass( ExampleSettings::class )]
#[UsesClass( ExampleWPSettings::class )]
final class GettingStartedTutorialTest extends TestCase {

	#[Test]
	public function classes_named_in_the_tutorial_exist(): void {
		$classes = array(
			Plugin::class,
			GenericFeature::class,
			WooCommerceFeature::class,
			Installer::class,
			WelcomeNotice::class,
			ExampleSettings::class,
			ExampleWPSettings::class,
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

	// §5's WooCommerce teardown is test-enforced at the symbol level: after deleting the files §5 removes,
	// the surviving WooCommerce references under src/ + config/ must match this exact per-file count map.
	// ANY new touchpoint — a new file, or one more reference inside an already-named file — fails here
	// until §5's prose covers it and the pin is updated.
	#[Test]
	public function section_5_enumerates_every_woocommerce_touchpoint(): void {
		$deleted_files   = array(
			'src/Feature/WooCommerceFeature.php',
			'src/Component/ExampleSettings.php',
			'src/Settings/ExampleWCSettingsPage.php',
		);
		$expected_counts = array(
			'config/container.php' => 5,
			'config/footprint.php' => 1,
			'src/Plugin.php'       => 6,
		);

		$counts = $this->reference_counts(
			'/Framework\\\\WooCommerce|WooCommerceFeature|ExampleWCSettingsPage|WooCommerceLogger|\bwc_[a-z_]+|woocommerce_[a-z_]+|ExampleSettings\b/i',
			$deleted_files
		);
		self::assertSame(
			$expected_counts,
			$counts,
			'The WooCommerce references surviving §5\'s file deletions changed — update §5\'s prose to cover the change, then this pin.'
		);

		$section = $this->tutorial_section( '## 5. WooCommerce, or not', '## 5b.' );
		foreach ( \array_merge( $deleted_files, \array_keys( $expected_counts ) ) as $file ) {
			self::assertStringContainsString(
				\basename( $file ),
				$section,
				"docs/getting-started.md §5 must name $file for removal or editing."
			);
		}
	}

	// The §5b mirror for WooCommerce-only forks: the generic settings demo's surviving references must
	// match this exact per-file count map, so a new touchpoint fails until §5b's prose covers it.
	#[Test]
	public function section_5b_enumerates_every_generic_settings_demo_touchpoint(): void {
		$deleted_files   = array(
			'src/Component/ExampleWPSettings.php',
		);
		$expected_counts = array(
			'config/footprint.php'           => 1,
			'src/Feature/GenericFeature.php' => 2,
		);

		$counts = $this->reference_counts( '/ExampleWPSettings|WordPressSettingsBackend/i', $deleted_files );
		self::assertSame(
			$expected_counts,
			$counts,
			'The generic-settings-demo references surviving §5b\'s file deletion changed — update §5b\'s prose to cover the change, then this pin.'
		);

		$section = $this->tutorial_section( '## 5b.', '## 6.' );
		foreach ( \array_merge( $deleted_files, \array_keys( $expected_counts ) ) as $file ) {
			self::assertStringContainsString(
				\basename( $file ),
				$section,
				"docs/getting-started.md §5b must name $file for removal or editing."
			);
		}
	}

	// phpcs:disable WordPress.WP.AlternativeFunctions -- the Unit suite runs without WordPress, so WP_Filesystem / wp_delete_file / wp_remote_get do not exist; plain PHP filesystem calls on the temp copy are the only option.

	/**
	 * Simulates a teardown's file deletions on a temp copy of src/ + config/ and returns a per-file match
	 * count of $pattern over the surviving files, keyed by plugin-relative path, sorted by key.
	 *
	 * @param non-empty-string $pattern
	 * @param list<string>     $deleted_files
	 *
	 * @return array<string, int>
	 */
	protected function reference_counts( string $pattern, array $deleted_files ): array {
		$root     = \dirname( __DIR__, 2 );
		$temp_dir = \sys_get_temp_dir() . '/dws-teardown-' . \uniqid();

		$copied = array();
		foreach ( array( 'src', 'config' ) as $tree ) {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( "$root/$tree", \FilesystemIterator::SKIP_DOTS )
			);
			foreach ( $iterator as $file ) {
				if ( ! $file instanceof \SplFileInfo || 'php' !== $file->getExtension() ) {
					continue;
				}
				$relative = \substr( $file->getPathname(), \strlen( $root ) + 1 );
				$target   = "$temp_dir/$relative";
				\is_dir( \dirname( $target ) ) || \mkdir( \dirname( $target ), 0777, true );
				\copy( $file->getPathname(), $target );
				$copied[] = $relative;
			}
		}

		try {
			foreach ( $deleted_files as $deleted ) {
				self::assertFileExists( "$temp_dir/$deleted", "The teardown list deletes $deleted, which must exist." );
				\unlink( "$temp_dir/$deleted" );
			}

			$counts = array();
			foreach ( $copied as $relative ) {
				if ( ! \is_file( "$temp_dir/$relative" ) ) {
					continue;
				}
				$matches = (int) \preg_match_all( $pattern, (string) \file_get_contents( "$temp_dir/$relative" ) );
				if ( $matches > 0 ) {
					$counts[ $relative ] = $matches;
				}
			}
			\ksort( $counts );

			return $counts;
		} finally {
			$this->delete_tree( $temp_dir );
		}
	}

	protected function delete_tree( string $directory ): void {
		if ( ! \is_dir( $directory ) ) {
			return;
		}
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $directory, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $iterator as $entry ) {
			if ( $entry instanceof \SplFileInfo ) {
				$entry->isDir() ? \rmdir( $entry->getPathname() ) : \unlink( $entry->getPathname() );
			}
		}
		\rmdir( $directory );
	}

	/**
	 * The tutorial text between two headings, for asserting a section names a file.
	 *
	 * @param non-empty-string $start
	 * @param non-empty-string $end
	 */
	protected function tutorial_section( string $start, string $end ): string {
		$tutorial = (string) \file_get_contents( \dirname( __DIR__, 2 ) . '/docs/getting-started.md' );

		$start_position = \strpos( $tutorial, $start );
		self::assertIsInt( $start_position, "docs/getting-started.md must contain the '$start' heading." );

		$end_position = \strpos( $tutorial, $end, $start_position );
		self::assertIsInt( $end_position, "docs/getting-started.md must contain the '$end' heading after '$start'." );

		return \substr( $tutorial, $start_position, $end_position - $start_position );
	}

	// phpcs:enable WordPress.WP.AlternativeFunctions
}
