import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@wordpress/e2e-test-utils-playwright';

const NAME = 'dws-plugin-template/example-notice';
const TEXT = 'An example notice from DWS Plugin Template.';

test.describe( 'Block editor', () => {
	let post: number | null = null;

	test.afterEach( async ( { requestUtils } ) => {
		if ( null !== post ) {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/posts/${ post }`,
				params: { force: true },
			} );
			post = null;
		}
	} );

	test( 'inserts the example notice from the command palette and publishes its Outlined style', async ( {
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
			.toMatchObject( [ { name: NAME } ] );
		await expect( editor.canvas.getByText( TEXT ) ).toBeVisible();

		await editor.openDocumentSettingsSidebar();
		const sidebar = page.getByRole( 'region', { name: 'Editor settings' } );
		// WordPress 7.2 renders block styles as radios, which earlier releases render as buttons.
		await sidebar
			.getByRole( 'radio', { name: 'Outlined' } )
			.or( sidebar.getByRole( 'button', { name: 'Outlined' } ) )
			.click();
		await expect
			.poll( editor.getBlocks )
			.toMatchObject( [
				{ name: NAME, attributes: { className: 'is-style-outlined' } },
			] );

		post = await editor.publishPost();
		expect( post ).not.toBeNull();
		await page.goto( `/?p=${ post }` );

		const notice = page.locator(
			'.wp-block-dws-plugin-template-example-notice.is-style-outlined'
		);
		await expect( notice ).toHaveText( TEXT );
		const results = await new AxeBuilder( { page } )
			.include( '.wp-block-dws-plugin-template-example-notice' )
			.analyze();
		expect( results.violations ).toEqual( [] );
	} );
} );
