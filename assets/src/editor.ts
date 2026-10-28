import { registerBlockStyle } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

registerBlockStyle( 'dws-plugin-template/example-notice', {
	name: 'outlined',
	label: __( 'Outlined', 'dws-plugin-template' ),
} );
