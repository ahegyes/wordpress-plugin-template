<?php declare( strict_types=1 );

/**
 * Mock-WordPress shims the plugin entrypoint reaches but the boot smoke does not: the requirements chain's
 * WP_Error / plugin metadata helpers and the setup notice's escaper. Each guard lets wp-env's real WordPress
 * win when present. Excluded from static analysis (see phpstan.dist.neon) so it cannot shadow wordpress-stubs.
 */

if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	// A directory with no readable plugin file, so get_plugin_metadata() takes its empty-metadata branch and
	// the requirements floor falls through to the framework minimums without loading wp-admin/includes.
	define( 'WP_PLUGIN_DIR', __DIR__ . '/nonexistent-plugins' );
}

if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename( $file ) {
		return 'dws-plugin-template/dws-plugin-template.php';
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $errors = array();

		private $error_data = array();

		public function add( $code, $message = '', $data = '' ) {
			$this->errors[ $code ][] = $message;
			if ( '' !== $data ) {
				$this->error_data[ $code ] = $data;
			}
		}

		public function has_errors() {
			return array() !== $this->errors;
		}

		public function get_error_codes() {
			return array_keys( $this->errors );
		}

		public function get_error_data( $code = '' ) {
			return isset( $this->error_data[ $code ] ) ? $this->error_data[ $code ] : null;
		}
	}
}
