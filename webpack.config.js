const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );
const path = require( 'path' );

const wcDepMap = {
	'@woocommerce/blocks-registry': [ 'wc', 'wcBlocksRegistry' ],
	'@woocommerce/settings': [ 'wc', 'wcSettings' ],
};

const wcHandleMap = {
	'@woocommerce/blocks-registry': 'wc-blocks-registry',
	'@woocommerce/settings': 'wc-settings',
};

const requestToExternal = ( request ) => {
	if ( wcDepMap[ request ] ) {
		return wcDepMap[ request ];
	}
};

const requestToHandle = ( request ) => {
	if ( wcHandleMap[ request ] ) {
		return wcHandleMap[ request ];
	}
};

module.exports = {
	...defaultConfig,
	entry: {
		payustandard: '/src/js/payustandard.js',
		payulistbanks: '/src/js/payulistbanks.js',
		payucreditcard: '/src/js/payucreditcard.js',
		payusecureform: '/src/js/payusecureform.js',
		payupaypo: '/src/js/payupaypo.js',
		payuklarna: '/src/js/payuklarna.js',
		payutwistopl: '/src/js/payutwistopl.js',
		payuinstallments: '/src/js/payuinstallments.js',
		payublik: '/src/js/payublik.js',
	},
	output: {
		path: path.resolve( __dirname, 'build/js/' ),
		filename: '[name].js',
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new WooCommerceDependencyExtractionWebpackPlugin( {
			requestToExternal,
			requestToHandle,
		} ),
	],
};
