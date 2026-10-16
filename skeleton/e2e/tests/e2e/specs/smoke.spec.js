/**
 * First browser tests of the project: extend them with tests of your blocks.
 * Each test deletes exactly what it created.
 */
const { test, expect } = require( '../fixtures' );
@@if example

/** The example block (src/Block/Teaser/). */
const TEASER = '__WP_NAMESPACE__/teaser';
@@endif

test.describe( '__NAME__', () => {
	test( 'renders the front page', async ( { page } ) => {
		const response = await page.goto( '/' );
		expect( response.status() ).toBe( 200 );
	} );
@@if example

	test( 'edits the teaser in the canvas and shows it on the front end', async ( {
		admin,
		editor,
		page,
		requestUtils,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: TEASER } );
		const teaser = editor.canvas.locator( `[data-type="${ TEASER }"]` );
		const title = teaser.locator( 'h2' );
		await expect( title ).toHaveAttribute( 'contenteditable', 'true' );
		await title.click();
		await page.keyboard.type( 'Hello teaser' );
		const id = await editor.publishPost();
		try {
			await page.goto( `/?p=${ id }` );
			await expect(
				page.locator( 'section h2', { hasText: 'Hello teaser' } )
			).toBeVisible();
		} finally {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/posts/${ id }`,
				params: { force: true },
			} );
		}
	} );
@@endif
} );
