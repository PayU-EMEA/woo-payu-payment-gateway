import woocommerce from '@woocommerce/eslint-plugin';
import globals from 'globals';

export default [
  ...woocommerce.configs.recommended,
  {
    settings: {
      'import/core-modules': [
        '@woocommerce/settings',
        '@woocommerce/blocks-registry',
        '@woocommerce/blocks-checkout',
        '@woocommerce/blocks-components',
        '@woocommerce/block-data',
      ],
    },
    languageOptions: {
      globals: { ...globals.browser, wp: 'readonly' },
    },
    rules: {
      '@typescript-eslint/no-unused-vars': [
        'error',
        {
          caughtErrorsIgnorePattern: '^_',
          argsIgnorePattern: '^_',
          varsIgnorePattern: '^_',
        },
      ],
      '@wordpress/i18n-text-domain': [
        'error',
        {
          allowedTextDomain: 'woo-payu-payment-gateway',
        },
      ],
    },
  },
  {
    files: [ 'eslint.config.mjs', '.prettierrc.js' ],
    rules: {
      '@typescript-eslint/no-require-imports': 'off',
    },
  },
];
