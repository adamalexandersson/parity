import { defineConfig } from 'vitest/config';

export default defineConfig({
    test: {
        environment: 'node',
        include: [
            'resources/js/**/*.test.js',
            'tests/vite/**/*.test.js',
        ],
    },
});
