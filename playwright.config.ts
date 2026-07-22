import { defineConfig, devices } from '@playwright/test';

/**
 * E2E de PLUMA Engine contra un WordPress real levantado por `wp-env`
 * (GOVERNANCE §4.1). Requiere `npm run wp-env start` antes de ejecutar.
 */
export default defineConfig({
    testDir: 'tests/e2e',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    workers: 1,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
    use: {
        baseURL: process.env.WP_BASE_URL ?? 'http://localhost:8888',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
