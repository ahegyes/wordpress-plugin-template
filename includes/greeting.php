<?php declare( strict_types=1 );
/**
 * Defines the function through which another plugin reads whether the account dashboard greeting is on.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @package DeepWebSolutions\PluginTemplate
 */

use DeepWebSolutions\PluginTemplate\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Returns whether the site has switched the account dashboard greeting on.
 *
 * It reads the stored setting through Options, as the greeting's gate does, and adds no filter. The gate reads it once, at boot, so a write later in the same request can make the two differ, and neither tells whether the greeting ran.
 *
 * @api
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return  bool
 */
function dws_tpl_is_greeting_enabled(): bool {
	return Options::get_bool( Options::GREETING_ENABLED );
}
