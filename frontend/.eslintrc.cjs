module.exports = {
  root: true,

  env: {
    browser: true,
    es2022: true,
  },

  parser: '@typescript-eslint/parser',

  parserOptions: {
    ecmaVersion: 'latest',
    sourceType: 'module',
    ecmaFeatures: {
      jsx: true,
    },
  },

  plugins: [
    'react',
    'react-hooks',
    'jsx-a11y',
    '@typescript-eslint',
  ],

  extends: [
    'eslint:recommended',
    'plugin:react/recommended',
    'plugin:react-hooks/recommended',
    'plugin:jsx-a11y/recommended',
    'plugin:@typescript-eslint/recommended',
  ],

  settings: {
    react: {
      version: 'detect',
    },
  },

  rules: {
    'react/react-in-jsx-scope': 'off',
  },

  ignorePatterns: [
    'dist/',
    'node_modules/',
  ],
};