const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );

module.exports = {
  ...defaultConfig,
  entry: {
    'js/payu': '/src/js/payu/main',
    'css/payu': '/src/css/main.scss',
    'js/payustandard': '/src/js/payustandard',
    'js/payulistbanks': '/src/js/payulistbanks',
    'js/payucreditcard': '/src/js/payucreditcard',
    'js/payusecureform': '/src/js/payusecureform',
    'js/payupaypo': '/src/js/payupaypo',
    'js/payuklarna': '/src/js/payuklarna',
    'js/payupragma': '/src/js/payupragma',
    'js/payutwistopl': '/src/js/payutwistopl',
    'js/payuinstallments': '/src/js/payuinstallments',
    'js/payublik': '/src/js/payublik',
    'js/payutwistoslice': '/src/js/payutwistoslice',
    'js/creditwidget': '/src/js/creditwidget',
  },
  plugins: [
    ...defaultConfig.plugins.filter(
      ( plugin ) =>
        plugin.constructor.name !== 'DependencyExtractionWebpackPlugin' &&
        plugin.constructor.name !== 'RtlCssPlugin'
    ),
    new RemoveEmptyScriptsPlugin(),
    new WooCommerceDependencyExtractionWebpackPlugin(),
  ],
};
