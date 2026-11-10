import { expect, test } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Block editor', () => {
	test( 'inserts the example notice from the command palette', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost();
		await editor.canvas
			.getByRole( 'textbox', { name: 'Add title' } )
			.fill( 'Example' );
		await page.keyboard.press( 'ControlOrMeta+k' );
		await page
			.getByRole( 'combobox', { name: 'Search commands and settings' } )
			.fill( 'Insert an example notice' );
		await page.keyboard.press( 'Enter' );

		await expect
			.poll( editor.getBlocks )
			.toMatchObject( [
				{ name: 'dws-plugin-template/example-notice' },
			] );
		await expect(
			editor.canvas.getByText(
				'An example notice from DWS Plugin Template.'
			)
		).toBeVisible();
	} );
} );
