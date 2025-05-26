const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		payustandard: '/src/js/payustandard',
		payulistbanks: '/src/js/payulistbanks',
		payucreditcard: '/src/js/payucreditcard',
		payusecureform: '/src/js/payusecureform',
		payupaypo: '/src/js/payupaypo',
		payuklarna: '/src/js/payuklarna',
		payutwistopl: '/src/js/payutwistopl',
		payuinstallments: '/src/js/payuinstallments',
		payublik: '/src/js/payublik',
		payutwistoslice: '/src/js/payutwistoslice',
	},
	resolve: {
		extensions: [ '.js', '.jsx', '.tsx', '.ts' ],
		fallback: {
			stream: false,
			path: false,
			fs: false,
		},
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
		new WooCommerceDependencyExtractionWebpackPlugin(),
	],
};
