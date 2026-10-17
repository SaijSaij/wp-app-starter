/**
 * Builds the editor runtime of the framework, the scripts in src/ and assets/
 * and the stylesheet assets/styles.css into build/ (see the webpack helper of
 * the framework, `@webcodingcat/wp-app-framework/webpack`).
 */
module.exports = require( '@webcodingcat/wp-app-framework/webpack' )( {
	slug: '__SLUG__',
	root: __dirname,
} );
