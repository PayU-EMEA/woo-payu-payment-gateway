module.exports = {
  extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
  settings: {
    'import/core-modules': [
      '@woocommerce/settings',
      '@woocommerce/blocks-registry',
      '@woocommerce/blocks-checkout',
      '@woocommerce/blocks-components',
    ],
  },
  rules: {
    camelcase: 0,
    'react/react-in-jsx-scope': 'off',
    '@woocommerce/dependency-group': 'off',
    '@wordpress/i18n-text-domain': [
      'error',
      {
        allowedTextDomain: 'woo-payu-payment-gateway',
      },
    ],
  },
};
