<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration\Greeting;

use DeepWebSolutions\PluginTemplate\Greeting\GreetingFeature;
use DeepWebSolutions\PluginTemplate\Plugin;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class GreetingFeatureTest extends TestCase {
	protected mixed $stored = false;

	public static function stored_toggles(): array {
		return array(
			'on'    => array( 'yes', true ),
			'off'   => array( 'no', false ),
			'unset' => array( null, false ),
		);
	}

	#[\Override]
	protected function setUp(): void {
		$this->stored = get_option( DWS_TPL_OPTION_GREETING_ENABLED );
	}

	#[After]
	protected function restore_row(): void {
		false === $this->stored ? delete_option( DWS_TPL_OPTION_GREETING_ENABLED ) : update_option( DWS_TPL_OPTION_GREETING_ENABLED, $this->stored );
	}

	#[DataProvider( 'stored_toggles' )]
	public function test_the_greeting_loads_only_where_a_site_switches_it_on( ?string $stored, bool $expected ): void {
		\is_null( $stored ) ? delete_option( DWS_TPL_OPTION_GREETING_ENABLED ) : update_option( DWS_TPL_OPTION_GREETING_ENABLED, $stored );

		self::assertSame( $expected, GreetingFeature::should_load() );
	}

	#[RunInSeparateProcess]
	public function test_with_the_greeting_switched_on_every_declared_component_boots(): void {
		update_option( DWS_TPL_OPTION_GREETING_ENABLED, 'yes' );
		$plugin = new Plugin();
		$plugin->boot();

		self::assertSame( 'booted', $plugin->boot->status );
		self::assertSame( array(), $plugin->kernel?->skipped );
	}
}
