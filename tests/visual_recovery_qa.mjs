import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import puppeteer from 'puppeteer-core';

const base = (process.env.PORTAL_QA_BASE_URL || 'http://127.0.0.1:8001').replace(/\/$/, '');
const out = path.resolve('docs/visual-qa');
fs.mkdirSync(out, { recursive: true });
const browser = await puppeteer.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true, args: ['--no-sandbox'] });
const page = await browser.newPage();
const resume = process.argv.includes('--resume');
const results = resume ? JSON.parse(fs.readFileSync(path.join(out,'results.json'),'utf8')) : { pages: [], interactions: [], errors: [], failedRequests: [] };
delete results.failure;
results.baseUrl = base;
if (resume) results.pages = results.pages.filter(p => !['admin-hero-slides','reader','authenticated-library'].includes(p.name));
page.on('pageerror', error => results.errors.push({ url: page.url(), message: error.message }));
page.on('console', message => { if (message.type() === 'error') results.errors.push({ url: page.url(), message: message.text() }); });
page.on('requestfailed', request => { if (!request.failure()?.errorText.includes('ERR_ABORTED')) results.failedRequests.push({ url: request.url(), error: request.failure()?.errorText }); });
const viewports = [[1920,1080],[1440,900],[1366,768],[1024,768],[768,1024],[390,844],[375,812]];
async function go(url) {
    const response = await page.goto(url.startsWith('http') || url.startsWith('file:') ? url : base + url, { waitUntil: 'load', timeout: 45000 });
    await page.evaluate(async () => { await document.fonts.ready; await Promise.allSettled([...document.images].map(image => { image.loading = "eager"; return image.decode(); })); });
    return response;
}
async function screenshot(name, fullPage = true) { await page.screenshot({ path: path.join(out, name + '.png'), fullPage }); }
async function metrics() {
    return page.evaluate(() => {
        const visible = element => element && getComputedStyle(element).display !== 'none' && element.getBoundingClientRect().height > 0;
        const cards = [...document.querySelectorAll('.insight')].map(card => {
            const media = card.querySelector('.insight-art').getBoundingClientRect();
            const text = card.querySelector('.insight-body').getBoundingClientRect();
            const image = card.querySelector('img');
            return { title: card.querySelector('h3').textContent, separated: text.top >= media.bottom - 1, imageLoaded: !image || image.complete && image.naturalWidth > 0 };
        });
        return { overflow: document.documentElement.scrollWidth > innerWidth, width: document.documentElement.scrollWidth,
            menuVisible: visible(document.querySelector('.menu')), desktopNavVisible: visible(document.querySelector('#nav')),
            heroColumns: document.querySelector('.hero-inner') ? getComputedStyle(document.querySelector('.hero-inner')).gridTemplateColumns : null,
            cards, brokenImages: [...document.images].filter(i => visible(i) && i.complete && !i.naturalWidth).map(i=>i.src),
            overflowingElements: [...document.querySelectorAll('main *')].filter(e=>visible(e) && e.getBoundingClientRect().right > innerWidth + 1).slice(0,8).map(e=>e.className || e.tagName)
        };
    });
}
try {
    await page.setViewport({width:1440,height:900});
    await go('/');
    const destinations = await page.evaluate(() => ({ book: document.querySelector('.book')?.href, insight: document.querySelector('.insight')?.href, category: document.querySelector('.category')?.href }));
    await go('/categories');
    destinations.category = await page.$eval('.category', e=>e.href);
    const pages = [['home','/'],['explore','/explore'],['categories','/categories'],['category',destinations.category],['insights','/insights'],['article',destinations.insight],['publication',destinations.book],['library','/my-library'],['login','/login'],['register','/register']];
    for (const [width,height] of resume ? [] : viewports) {
        await page.setViewport({width,height});
        for (const [name,url] of pages) {
            if (!url) continue;
            const response = await go(url);
            const details = await metrics();
            await screenshot(`${name}-${width}`);
            results.pages.push({name,viewport:`${width}x${height}`,status:response.status(),...details});
        }
        console.log(`Captured and measured ${width}x${height}`);
    }
    await page.setViewport({width:1440,height:900});
    await go('/');
    await page.click('.slider-play');
    const currentTitle = () => page.$eval('.hero-slider', e=>[...e.querySelectorAll('.hero-slide')].find(s=>getComputedStyle(s).display!=='none').querySelector('h2').textContent);
    const first = await currentTitle();
    await page.click('[aria-label="Next slide"]');
    assert.notEqual(await currentTitle(), first);
    await page.click('[aria-label="Previous slide"]');
    assert.equal(await currentTitle(), first);
    await page.click('[aria-label="Show slide 3"]');
    assert.equal(await page.$eval('[aria-label="Show slide 3"]', e=>e.getAttribute('aria-pressed')), 'true');
    results.interactions.push('Slider next, previous, pagination, pause');
    await page.type('.hero-copy input[name=q]', 'MFANO');
    await Promise.all([page.waitForNavigation({waitUntil:'networkidle2'}),page.click('.hero-copy button[type=submit]')]);
    assert(new URL(page.url()).searchParams.get('q') === 'MFANO');
    assert(await page.$('.book'));
    results.interactions.push('Hero search submission and matching publication');
    await go('/');
    await Promise.all([page.waitForNavigation({waitUntil:'networkidle2'}),page.click('.chip')]);
    assert(new URL(page.url()).searchParams.get('q') === 'Leadership');
    results.interactions.push('Topic chip search');
    await go(destinations.book);
    const readerUrl = await page.$eval('a[href$="/reader"]', e=>e.href);
    await go(readerUrl);
    if (page.url().includes('/login')) results.interactions.push('Guest reader redirected for restricted publication');
    else { assert(await page.$('#pdf-canvas')); results.interactions.push('Guest access to public publication preserved; restricted-access redirects covered by PHPUnit'); }
    await page.setViewport({width:390,height:844});
    await go('/');
    await page.click('.menu');
    assert.equal(await page.$eval('.menu',e=>e.getAttribute('aria-expanded')),'true');
    await page.waitForFunction(()=>document.activeElement?.matches('#nav a'));
    assert.equal(await page.evaluate(()=>document.activeElement?.textContent.trim()),'Home');
    await screenshot('mobile-menu-390');
    await page.keyboard.press('Escape');
    assert.equal(await page.$eval('.menu',e=>e.getAttribute('aria-expanded')),'false');
    assert.equal(await page.evaluate(()=>document.activeElement?.className),'menu');
    results.interactions.push('Mobile menu opens, focuses first link, Escape closes and restores focus');
    await page.emulateMediaFeatures([{name:'prefers-reduced-motion',value:'reduce'}]);
    await go('/');
    assert.equal(await page.$eval('.slider-play',e=>e.textContent),'Play');
    results.interactions.push('Reduced motion disables autoplay');
    await page.emulateMediaFeatures([]);
    await page.setViewport({width:1440,height:900});
    await go('/login');
    await page.click('.notice button');
    await Promise.all([page.waitForNavigation({waitUntil:'networkidle2'}),page.click('button[type=submit]')]);
    assert(new URL(page.url()).pathname.endsWith('/admin'));
    for (const [width,height] of viewports) {
        await page.setViewport({width,height});
        const response = await go('/admin/hero-slides');
        await screenshot(`admin-hero-slides-${width}`);
        assert(await page.$$eval('.admin-topbar-actions a', links => links.every(a => a.getBoundingClientRect().right <= innerWidth && a.getBoundingClientRect().left >= 0)), 'Admin topbar actions must fit');
        if (width <= 768) {
            await page.$eval('.table-responsive', e => { e.scrollLeft = e.scrollWidth; });
            await page.focus('.hero-management-table a[href$="/edit"]');
            assert(await page.$eval('.hero-management-table a[href$="/edit"]', e => { const rect=e.getBoundingClientRect(); return rect.left>=0 && rect.right<=innerWidth; }), 'Slide edit action must be reachable');
            await screenshot(`admin-hero-slide-actions-${width}`);
        }
        results.pages.push({name:'admin-hero-slides',viewport:`${width}x${height}`,status:response.status(),...await metrics()});
        const readerResponse = await go(readerUrl);
        assert.equal(readerResponse.status(), 200);
        await page.waitForFunction(()=>Number(document.querySelector('#total-pages')?.textContent) > 0 && document.querySelector('#pdf-canvas')?.width > 0 && document.querySelector('#loading-spinner')?.style.display === 'none',{timeout:20000});
        await screenshot(`reader-${width}`);
        results.pages.push({name:'reader',viewport:`${width}x${height}`,status:200,...await metrics(), pdfPages:await page.$eval('#total-pages',e=>e.textContent)});
    }
    results.interactions.push('Admin login, hero administration and authenticated PDF rendering');
    await page.setViewport({width:390,height:844});
    await go('/my-library');
    await screenshot('authenticated-library-390');
    results.pages.push({name:'authenticated-library',viewport:'390x844',status:200,...await metrics()});
    await go('file:///C:/wamp64/www/portal/prototype_backup/index.html');
    await screenshot('prototype-390');
    assert(results.pages.every(p=>p.status===200), 'Every reviewed page must return HTTP 200');
    assert(results.pages.every(p=>!p.overflow), 'No document may overflow horizontally');
    assert(results.pages.every(p=>p.cards.every(c=>c.separated && c.imageLoaded)), 'Insight content must remain below loaded media');
    assert.equal(results.errors.length,0,'Browser console and runtime must be clear');
} catch (error) {
    results.failure = error.stack;
    console.error(error.stack);
    process.exitCode = 1;
} finally {
    results.interactions = [...new Set(results.interactions)];
    fs.writeFileSync(path.join(out,'results.json'),JSON.stringify(results,null,2));
    console.log(JSON.stringify({pages:results.pages.length,overflow:results.pages.filter(p=>p.overflow).map(p=>[p.name,p.viewport,p.overflowingElements]),overlap:results.pages.filter(p=>p.cards.some(c=>!c.separated)).map(p=>[p.name,p.viewport]),interactions:results.interactions,errors:results.errors,failedRequests:results.failedRequests,failure:results.failure},null,2));
    await browser.close();
}
