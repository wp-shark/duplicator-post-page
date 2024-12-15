import { defineConfig } from '@playwright/test'; // Use import syntax

// playwright.config.js
export default defineConfig({
    // Timeout for each test
    timeout: 999999,
    fullyParallel: true,
    reporter: "html",

    // Maximum number of test failures for the whole test suite run
    // to be considered successful
    globalTimeout: 0,

    // Whether to run tests in parallel
    // or sequentially
    workers: 1,

    // Test files to run
    testDir: './tests',

    // Browser to use for tests
    use: {
        // Use Chromium browser
        // You can also use 'firefox' or 'webkit'
        browserName: process.env.BROWSER || 'chromium',
        launchOptions: {
            slowMo: process.env.SLOWMO ? parseInt(process.env.SLOWMO) : 500,
            headless: false,
        },
    },

    // Configure web server
    webServer: {
        command: process.env.MODE === 'dev' ? 'npm run watch' : 'npm run build',
        url: process.env.SERVER_URL || 'http://localhost/wptester/wp-admin/',
        timeout: 120 * 1000,
        reuseExistingServer: true,
    },
});
