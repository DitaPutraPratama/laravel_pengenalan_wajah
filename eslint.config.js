import js from '@eslint/js';
import globals from 'globals';

export default [
    { ignores: ['public/build/**', 'vendor/**', 'node_modules/**'] },
    js.configs.recommended,
    {
        files: ['resources/js/**/*.js'],
        languageOptions: { globals: globals.browser },
    },
    {
        files: ['tests/js/**/*.js', '*.config.js'],
        languageOptions: { globals: globals.node },
    },
];
