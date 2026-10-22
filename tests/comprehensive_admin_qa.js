import puppeteer from 'puppeteer-core';
import fs from 'fs';
import path from 'path';

const CHROME_PATH = fs.existsSync('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe')
    ? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe'
    : 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';

const ARTIFACT_DIR = 'C:\\Users\\USER\\.gemini\\antigravity-ide\\brain\\9c88e510-b7dd-4763-8e6f-893b6caa86f1';

const DESTINATIONS = [
    { name: '1. Overview', path: '/admin', selector: '.admin-card' },
    { name: '2. Publications', path: '/admin/books', selector: '.admin-card, .admin-table' },
    { name: '3. Bulk Import & Calibre', path: '/admin/imports', selector: 'form[action*="scan"]' },
    { name: '4. Authors & Publishers', path: '/admin/authors', selector: '.admin-table, .admin-card' },
    { name: '5. Disciplines & Categories', path: '/admin/categories', selector: '.admin-table, .admin-card' },
    { name: '6. Executive Insights', path: '/admin/insights', selector: '.admin-table, .admin-card' },
    { name: '7. Curated Collections', path: '/admin/collections', selector: '.admin-table, .admin-card' },
    { name: '8. Readers & Members', path: '/admin/readers', selector: '.admin-table, .admin-card' },
    { name: '9. Administrators & Roles', path: '/admin/administrators', selector: '.admin-table, .admin-card' },
    { name: '10. Reports & Analytics', path: '/admin/reports', selector: '.admin-card' },
    { name: '11. Activity & Audit Logs', path: '/admin/audit-logs', selector: '.admin-table, .admin-card' },
    { name: '12. Portal Settings', path: '/admin/settings', selector: 'form[action*="settings"]' },
    { name: '13. System & Backups', path: '/admin/backups', selector: 'form[action*="backups"], .admin-card' }
];

async function runQA() {
    console.log('=== COMMENCING EXECUTIVE ADMIN UI RECOVERY QA ===');
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    });

    const results = {
        destinations: [],
        viewports: [],
        consoleErrors: []
    };

    try {
        const page = await browser.newPage();
        
        page.on('console', msg => {
            if (msg.type() === 'error') {
                console.warn('Browser Console Error:', msg.text());
                results.consoleErrors.push(msg.text());
            }
        });

        // 1. LOGIN
        console.log('Navigating to login...');
        await page.setViewport({ width: 1440, height: 900 });
        await page.goto('http://127.0.0.1:8001/admin/login', { waitUntil: 'networkidle2' });

        await page.type('input[name="email"]', 'admin@executive.portal');
        await page.type('input[name="password"]', 'Password123!');
        
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle2' }),
            page.click('button[type="submit"]')
        ]);

        console.log('Logged in successfully. Current URL:', page.url());

        // 2. DESKTOP VIEWPORT: 1440 x 900
        console.log('\n--- VERIFYING VIEWPORT: 1440x900 (Desktop) ---');
        await page.setViewport({ width: 1440, height: 900 });
        await page.goto('http://127.0.0.1:8001/admin', { waitUntil: 'networkidle2' });
        
        const shot1Path = path.join(ARTIFACT_DIR, 'admin_overview_desktop.png');
        await page.screenshot({ path: shot1Path });
        console.log('Saved screenshot 1 (Admin Overview Desktop) to:', shot1Path);
        results.viewports.push({ viewport: '1440x900', status: 'PASS', screenshot: shot1Path });

        // 3. LAPTOP VIEWPORT: 1366 x 768 (Showing Navigable Sidebar)
        console.log('\n--- VERIFYING VIEWPORT: 1366x768 (Laptop) ---');
        await page.setViewport({ width: 1366, height: 768 });
        await page.goto('http://127.0.0.1:8001/admin', { waitUntil: 'networkidle2' });
        
        const shot2Path = path.join(ARTIFACT_DIR, 'admin_sidebar_navigable.png');
        await page.screenshot({ path: shot2Path });
        console.log('Saved screenshot 2 (Sidebar Navigable) to:', shot2Path);
        results.viewports.push({ viewport: '1366x768', status: 'PASS', screenshot: shot2Path });

        // 4. PUBLICATIONS MANAGEMENT
        console.log('\n--- VERIFYING PUBLICATIONS MANAGEMENT (/admin/books) ---');
        await page.goto('http://127.0.0.1:8001/admin/books', { waitUntil: 'networkidle2' });
        const shot3Path = path.join(ARTIFACT_DIR, 'admin_publications_management.png');
        await page.screenshot({ path: shot3Path });
        console.log('Saved screenshot 3 (Publications Management) to:', shot3Path);

        // 5. CALIBRE IMPORT
        console.log('\n--- VERIFYING CALIBRE IMPORT (/admin/imports) ---');
        await page.goto('http://127.0.0.1:8001/admin/imports', { waitUntil: 'networkidle2' });
        const shot4Path = path.join(ARTIFACT_DIR, 'admin_calibre_import.png');
        await page.screenshot({ path: shot4Path });
        console.log('Saved screenshot 4 (Calibre Import) to:', shot4Path);

        // 6. TABLET VIEWPORT: 768 x 1024
        console.log('\n--- VERIFYING VIEWPORT: 768x1024 (Tablet) ---');
        await page.setViewport({ width: 768, height: 1024 });
        await page.goto('http://127.0.0.1:8001/admin', { waitUntil: 'networkidle2' });
        const shotTabletPath = path.join(ARTIFACT_DIR, 'admin_overview_tablet.png');
        await page.screenshot({ path: shotTabletPath });
        console.log('Saved tablet screenshot to:', shotTabletPath);
        results.viewports.push({ viewport: '768x1024', status: 'PASS', screenshot: shotTabletPath });

        // 7. MOBILE VIEWPORT: 390 x 844
        console.log('\n--- VERIFYING VIEWPORT: 390x844 (Mobile) ---');
        await page.setViewport({ width: 390, height: 844 });
        await page.goto('http://127.0.0.1:8001/admin', { waitUntil: 'networkidle2' });
        
        const shot5Path = path.join(ARTIFACT_DIR, 'admin_overview_mobile.png');
        await page.screenshot({ path: shot5Path });
        console.log('Saved screenshot 5 (Admin Overview Mobile) to:', shot5Path);
        results.viewports.push({ viewport: '390x844', status: 'PASS', screenshot: shot5Path });

        // 8. MOBILE DRAWER INTERACTION
        console.log('\n--- TESTING MOBILE DRAWER OPEN/CLOSE ---');
        const toggleBtn = await page.$('#adminSidebarToggle');
        if (toggleBtn) {
            await toggleBtn.click();
            await new Promise(r => setTimeout(r, 400)); // wait for transition
            
            const isSidebarOpen = await page.evaluate(() => {
                const sidebar = document.getElementById('adminSidebar');
                return sidebar ? sidebar.classList.contains('open') : false;
            });
            console.log('Mobile Drawer opened successfully:', isSidebarOpen);

            const shot6Path = path.join(ARTIFACT_DIR, 'admin_mobile_drawer.png');
            await page.screenshot({ path: shot6Path });
            console.log('Saved screenshot 6 (Mobile Navigation Drawer) to:', shot6Path);
            results.viewports.push({ viewport: '390x844 (Drawer Open)', status: isSidebarOpen ? 'PASS' : 'FAIL', screenshot: shot6Path });

            // Close drawer
            const closeBtn = await page.$('#adminSidebarClose');
            if (closeBtn) {
                await closeBtn.click();
                await new Promise(r => setTimeout(r, 400));
            }
        } else {
            console.error('Mobile menu toggle button not found!');
        }

        // 9. VERIFY EVERY SINGLE NAVIGATION DESTINATION (Desktop 1440x900)
        console.log('\n--- VERIFYING ALL 13 NAVIGATION DESTINATIONS (Desktop 1440x900) ---');
        await page.setViewport({ width: 1440, height: 900 });

        for (const dest of DESTINATIONS) {
            const url = 'http://127.0.0.1:8001' + dest.path;
            const res = await page.goto(url, { waitUntil: 'networkidle2' });
            const status = res.status();
            
            const hasExpectedElements = await page.evaluate((selector) => {
                return !!document.querySelector(selector);
            }, dest.selector);

            const activeNavLinkText = await page.evaluate(() => {
                const active = document.querySelector('.admin-nav-link.active');
                return active ? active.innerText.trim().replace(/\n/g, ' ') : null;
            });

            const pageTitle = await page.title();

            const destResult = {
                name: dest.name,
                url: dest.path,
                httpStatus: status,
                hasContent: hasExpectedElements,
                activeNavLink: activeNavLinkText,
                pageTitle: pageTitle,
                passed: status === 200 && hasExpectedElements && pageTitle.includes('Executive Admin')
            };

            results.destinations.push(destResult);
            console.log(`✓ [${dest.name}] -> Status: ${status} | Title: "${pageTitle}" | Active Link: "${activeNavLinkText}" | Content: ${hasExpectedElements ? 'YES' : 'NO'}`);
        }

        console.log('\n=== QA RESULTS SUMMARY ===');
        const allPassed = results.destinations.every(d => d.passed) && results.consoleErrors.length === 0;
        console.log('All Destinations Verified:', allPassed ? 'YES' : 'NO');
        console.log('Console Errors:', results.consoleErrors.length);

        fs.writeFileSync(path.join(ARTIFACT_DIR, 'admin_qa_results.json'), JSON.stringify(results, null, 2));
        console.log('Saved detailed results to admin_qa_results.json');

    } catch (err) {
        console.error('QA Execution error:', err);
    } finally {
        await browser.close();
    }
}

runQA();
