import { store as blockEditorStore } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import { store as commandsStore } from '@wordpress/commands';
import { dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

dispatch( commandsStore ).registerCommand( {
	name: 'dws-plugin-template/insert-example-notice',
	label: __( 'Insert an example notice', 'dws-plugin-template' ),
	callback: ( { close } ) => {
		dispatch( blockEditorStore ).insertBlocks(
			createBlock( 'dws-plugin-template/example-notice' )
		);
		close();
	},
} );
