import { defineConfig } from 'vite';

export default defineConfig({
    define: {
        'process.env.NODE_ENV': JSON.stringify('production'),
    },
    esbuild: {
        jsx: 'transform',
        jsxFactory: 'createElement',
        jsxFragment: 'Fragment',
    },
    build: {
        lib: {
            entry: 'resources/js/runtime.js',
            name: 'sprout',
            formats: ['iife'],
            fileName: () => 'sprout.js',
        },
        outDir: 'dist',
        emptyOutDir: true,
        rollupOptions: {
            external: [
                '@wordpress/element',
                '@wordpress/block-editor',
                '@wordpress/components',
                '@wordpress/i18n',
            ],
            output: {
                globals: {
                    '@wordpress/element': 'wp.element',
                    '@wordpress/block-editor': 'wp.blockEditor',
                    '@wordpress/components': 'wp.components',
                    '@wordpress/i18n': 'wp.i18n',
                },
            },
        },
    },
});
