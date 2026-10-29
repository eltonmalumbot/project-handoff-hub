import { defineConfig } from '@playwright/test';
export default defineConfig({
 testDir: './tests/Browser', testMatch: '**/*.spec.js', fullyParallel: false, workers: 1,
 timeout: 90000, expect: { timeout: 10000 },
 use: { baseURL: 'http://127.0.0.1:8000', acceptDownloads: true, launchOptions: { executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE || '/usr/bin/chromium', args: ['--no-sandbox'] }, screenshot: 'only-on-failure', trace: 'retain-on-failure' },
 reporter: [['list'],['html',{open:'never'}]],
});
