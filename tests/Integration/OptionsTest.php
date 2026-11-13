<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration;

use DeepWebSolutions\PluginTemplate\Options;
use DeepWebSolutions\PluginTemplate\Plugin;
use DeepWebSolutions\PluginTemplate\Tests\Support\RestoresOptionsTrait;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class OptionsTest extends TestCase {
	use RestoresOptionsTrait;

	protected const string KEY = Plugin::PREFIX . '_options_test';

	protected const array OPTIONS = array( Options::DELETE_DATA, Options::GREETING, Options::GREETING_ORDERS );

	protected const string TRANSLATION = 'Translated greeting';

	public static function stored_bools(): array {
		return array(
			'yes'       => array( 'yes', true ),
			'no'        => array( 'no', false ),
			'one'       => array( '1', true ),
			'zero'      => array( '0', false ),
			'true'      => array( true, true ),
			'empty'     => array( '', false ),
			'malformed' => array( 'maybe', false ),
			'array'     => array( array( 'yes' ), false ),
		);
	}

	public static function stored_ints(): array {
		return array(
			'int'       => array( 7, 7 ),
			'numeric'   => array( '7', 7 ),
			'padded'    => array( ' 7 ', 7 ),
			'negative'  => array( '-7', -7 ),
			'float'     => array( '7.5', 0 ),
			'malformed' => array( 'seven', 0 ),
			'array'     => array( array( 7 ), 0 ),
		);
	}

	public static function stored_strings(): array {
		return array(
			'string' => array( 'kept', 'kept' ),
			'empty'  => array( '', '' ),
			'array'  => array( array( 'kept' ), '' ),
		);
	}

	#[After]
	protected function delete_the_test_option(): void {
		delete_option( self::KEY );
	}

	#[DataProvider( 'stored_bools' )]
	public function test_a_bool_reads_the_stored_checkbox_value_or_false( mixed $stored, bool $expected ): void {
		update_option( Options::DELETE_DATA, $stored );

		self::assertSame( $expected, Options::get_bool( Options::DELETE_DATA ) );
	}

	public function test_a_missing_option_reads_its_default(): void {
		delete_option( Options::DELETE_DATA );

		self::assertSame( Options::DEFAULTS[ Options::DELETE_DATA ], Options::get_bool( Options::DELETE_DATA ) );
	}

	#[DataProvider( 'stored_ints' )]
	public function test_an_int_reads_a_whole_number_or_zero( mixed $stored, int $expected ): void {
		update_option( self::KEY, $stored );

		self::assertSame( $expected, Options::get_int( self::KEY ) );
	}

	public function test_an_int_that_is_not_a_whole_number_reads_its_default(): void {
		update_option( Options::GREETING_ORDERS, 'seven' );

		self::assertSame( Options::DEFAULTS[ Options::GREETING_ORDERS ], Options::get_int( Options::GREETING_ORDERS ) );
	}

	#[DataProvider( 'stored_strings' )]
	public function test_a_string_reads_a_stored_string_or_the_empty_string( mixed $stored, string $expected ): void {
		update_option( self::KEY, $stored );

		self::assertSame( $expected, Options::get_string( self::KEY ) );
	}

	public function test_a_string_without_a_stored_value_reads_as_empty_and_untranslated(): void {
		delete_option( Options::GREETING );
		$translate = static fn (): string => self::TRANSLATION;
		add_filter( 'gettext_' . Plugin::SLUG, $translate );

		try {
			self::assertSame( '', Options::get_string( Options::GREETING ) );
		} finally {
			remove_filter( 'gettext_' . Plugin::SLUG, $translate );
		}
	}
}
