/// <reference types="vitest/config" />
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [react()],
    build: {
        outDir: 'build/panel',
        manifest: true,
        emptyOutDir: true,
        rollupOptions: {
            input: 'panel/src/main.tsx',
        },
    },
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: ['panel/src/setupTests.ts'],
        include: ['panel/src/**/*.test.{ts,tsx}'],
    },
});
