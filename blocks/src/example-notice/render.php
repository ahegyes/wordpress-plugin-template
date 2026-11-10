<?php declare( strict_types=1 );
/**
 * Prints the example notice, which renders on each request so that every visitor reads it in their own language.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @package DeepWebSolutions\PluginTemplate
 */

defined( 'ABSPATH' ) || exit;

?>
<p <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress escapes every attribute it returns. ?>><?php esc_html_e( 'An example notice from DWS Plugin Template.', 'dws-plugin-template' ); ?></p>
