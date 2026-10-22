import puppeteer from 'puppeteer-core';
import fs from 'fs';
import path from 'path';

const CHROME_PATH = fs.existsSync('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe')
    ? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe'
    : 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';

const ARTIFACT_DIR = 'C:\\Users\\USER\\.gemini\\antigravity-ide\\brain\\9c88e510-b7dd-4763-8e6f-893b6caa86f1';

async function run() {
    console.log('Launching browser at:', CHROME_PATH);
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    });

    try {
        const page = await browser.newPage();
        await page.setViewport({ width: 1366, height: 768 });

        console.log('Navigating to login...');
        await page.goto('http://127.0.0.1:8001/admin/login', { waitUntil: 'networkidle2' });

        await page.type('input[name="email"]', 'admin@executive.portal');
        await page.type('input[name="password"]', 'Password123!');
        
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle2' }),
            page.click('button[type="submit"]')
        ]);

        console.log('Logged in successfully. Current URL:', page.url());

        // Capture BEFORE screenshot of /admin
        const beforeScreenshotPath = path.join(ARTIFACT_DIR, 'admin_overview_before_recovery.png');
        await page.screenshot({ path: beforeScreenshotPath });
        console.log('Saved before screenshot to:', beforeScreenshotPath);

        // Inspect sidebar computed layout and elements
        const sidebarDiagnostics = await page.evaluate(() => {
            const sidebar = document.querySelector('.admin-sidebar');
            const nav = sidebar ? sidebar.querySelector('nav') : null;
            const links = nav ? Array.from(nav.querySelectorAll('a')) : [];
            const labels = nav ? Array.from(nav.querySelectorAll('.sidebar-section-label')) : [];

            const sidebarStyle = sidebar ? window.getComputedStyle(sidebar) : null;
            const navStyle = nav ? window.getComputedStyle(nav) : null;

            return {
                sidebar: sidebar ? {
                    width: sidebarStyle.width,
                    height: sidebarStyle.height,
                    overflowY: sidebarStyle.overflowY,
                    position: sidebarStyle.position,
                    display: sidebarStyle.display,
                    flexDirection: sidebarStyle.flexDirection,
                    scrollHeight: sidebar.scrollHeight,
                    clientHeight: sidebar.clientHeight,
                    boundingRect: sidebar.getBoundingClientRect()
                } : null,
                nav: nav ? {
                    display: navStyle.display,
                    flexDirection: navStyle.flexDirection,
                    flexWrap: navStyle.flexWrap,
                    gap: navStyle.gap,
                    position: navStyle.position,
                    width: navStyle.width,
                    height: navStyle.height,
                    scrollHeight: nav.scrollHeight,
                    clientHeight: nav.clientHeight,
                    boundingRect: nav.getBoundingClientRect()
                } : null,
                linkCount: links.length,
                labelCount: labels.length,
                links: links.map(a => {
                    const rect = a.getBoundingClientRect();
                    const style = window.getComputedStyle(a);
                    return {
                        text: a.innerText.trim().replace(/\n/g, ' '),
                        href: a.href,
                        rect: { x: rect.x, y: rect.y, width: rect.width, height: rect.height },
                        color: style.color,
                        visibility: style.visibility,
                        display: style.display
                    };
                })
            };
        });

        console.log('=== SIDEBAR DIAGNOSTICS ===');
        console.log(JSON.stringify(sidebarDiagnostics, null, 2));

    } catch (err) {
        console.error('Error during QA:', err);
    } finally {
        await browser.close();
    }
}

run();
