import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@wordpress/e2e-test-utils-playwright';
import type { Locator, Page } from '@playwright/test';
import { cli } from './cli';

const SAVED = 'Your settings have been saved.';

const tabTo = async ( page: Page, target: Locator ): Promise< void > => {
	for ( let presses = 0; presses < 10; presses++ ) {
		await page.keyboard.press( 'Tab' );
		if (
			await target.evaluate(
				( element ) => element === element.ownerDocument.activeElement
			)
		) {
			return;
		}
	}
	throw new Error( 'Tab never reached the element.' );
};

const typeAndSave = async ( page: Page, value: string ): Promise< void > => {
	await page.keyboard.press( 'ControlOrMeta+a' );
	await page.keyboard.press( 'Backspace' );
	await page.keyboard.type( value );
	await tabTo( page, page.getByRole( 'button', { name: 'Save changes' } ) );
	await page.keyboard.press( 'Enter' );
	await page.waitForLoadState();
};

test.describe( 'Settings tab', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.visitAdminPage(
			'admin.php',
			'page=wc-settings&tab=dws_tpl'
		);
	} );

	test( 'has no accessibility violations in its form', async ( { page } ) => {
		const results = await new AxeBuilder( { page } )
			.include( '#mainform' )
			.analyze();

		expect( results.violations ).toEqual( [] );
	} );

	test( 'saves the order threshold by keyboard, and an emptied one as the default its placeholder shows', async ( {
		page,
	} ) => {
		const threshold = page.getByLabel( 'Orders before the greeting' );

		await page.getByLabel( 'Message' ).focus();
		await tabTo( page, threshold );
		await typeAndSave( page, '3' );
		await expect( page.getByText( SAVED ) ).toBeVisible();
		await expect( threshold ).toHaveValue( '3' );

		await threshold.focus();
		await typeAndSave( page, '' );
		await expect( page.getByText( SAVED ) ).toBeVisible();
		await expect( threshold ).toHaveValue( '' );
		await expect( threshold ).toHaveAttribute( 'placeholder', '1' );
		expect(
			cli(
				'eval',
				'var_export( get_option( "dws_tpl_greeting_orders" ) );'
			)
		).toBe( "''" );
		const emptied = await new AxeBuilder( { page } )
			.include( '#mainform' )
			.analyze();
		expect( emptied.violations ).toEqual( [] );

		await threshold.focus();
		await typeAndSave( page, '1' );
		await expect( page.getByText( SAVED ) ).toBeVisible();
		await expect( threshold ).toHaveValue( '1' );
	} );
} );
