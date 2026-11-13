<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration\Greeting;

use DeepWebSolutions\PluginTemplate\Greeting\GreetingFeature;
use DeepWebSolutions\PluginTemplate\Options;
use DeepWebSolutions\PluginTemplate\Plugin;
use DeepWebSolutions\PluginTemplate\Tests\Support\OrderStorageTrait;
use DeepWebSolutions\PluginTemplate\Tests\Support\RestoresOptionsTrait;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class GreetingFeatureTest extends TestCase {
	use OrderStorageTrait;
	use RestoresOptionsTrait;

	protected const array OPTIONS = array( Options::GREETING_ENABLED, Options::GREETING, Options::GREETING_ORDERS );

	public static function stored_toggles(): array {
		return array(
			'on'    => array( 'yes', true ),
			'off'   => array( 'no', false ),
			'unset' => array( null, false ),
		);
	}

	#[DataProvider( 'stored_toggles' )]
	public function test_the_greeting_loads_only_where_a_site_switches_it_on( ?string $stored, bool $expected ): void {
		\is_null( $stored ) ? delete_option( Options::GREETING_ENABLED ) : update_option( Options::GREETING_ENABLED, $stored );

		self::assertSame( $expected, GreetingFeature::should_load() );
	}

	#[RunInSeparateProcess]
	public function test_with_the_greeting_switched_on_the_boot_greets_a_customer_on_the_account_dashboard(): void {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		add_filter( 'pre_wp_mail', '__return_false' ); // A completed order emails its customer, and the mailer's stderr fails a row in a separate process.
		update_option( Options::GREETING_ENABLED, 'yes' );
		update_option( Options::GREETING, 'Welcome back.' );
		update_option( Options::GREETING_ORDERS, '1' );
		$customer = wp_insert_user(
			array(
				'user_login' => Plugin::PREFIX . '_customer_' . wp_generate_password( 8, false ),
				'user_pass'  => wp_generate_password(),
				'role'       => 'customer',
			)
		);
		self::assertIsInt( $customer );

		try {
			$this->create_order(
				array(
					'customer_id' => $customer,
					'status'      => 'completed',
				)
			);
			wp_set_current_user( $customer );
			$plugin = new Plugin();
			$plugin->boot();
			\ob_start();
			do_action( 'woocommerce_account_dashboard' );
			$dashboard = (string) \ob_get_clean();

			self::assertSame( 'booted', $plugin->boundary->status );
			self::assertSame( array(), $plugin->kernel?->skipped );
			self::assertStringContainsString( 'Welcome back.', $dashboard );
		} finally {
			wp_set_current_user( 0 );
			wp_delete_user( $customer );
		}
	}
}
