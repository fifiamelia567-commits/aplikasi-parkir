const puppeteer = require('puppeteer-core');
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://127.0.0.1:8899/aplikasi-parkir';
(async () => {
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox','--disable-dev-shm-usage'] });
  const out = [];

  // --- DARK MODE check on all pages ---
  const ctx = await browser.createBrowserContext();
  const page = await ctx.newPage();
  await page.setViewport({ width: 1440, height: 1000 });
  await page.goto(BASE + '/auth/login.php', { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('#username');
  await page.type('#username','admin1'); await page.type('#password','123456');
  await Promise.all([page.waitForNavigation({waitUntil:'networkidle0'}), page.click('button[type=submit]')]);

  // toggle dark
  const hasToggle = await page.$('.theme-toggle');
  out.push('theme-toggle exists: ' + (hasToggle ? 'YES' : 'NO'));
  if (hasToggle) {
    await page.click('.theme-toggle');
    await new Promise(r=>setTimeout(r,500));
    const theme = await page.evaluate(() => document.documentElement.getAttribute('data-theme'));
    const bg = await page.evaluate(() => getComputedStyle(document.body).backgroundColor);
    const topbar = await page.evaluate(() => getComputedStyle(document.querySelector('.topbar')).backgroundImage.slice(0,60));
    out.push('after toggle data-theme=' + theme + ' bodyBg=' + bg);
    out.push('dark topbar=' + topbar);
  }
  // check all pages in dark mode
  for (const p of ['/admin/dashboard.php','/admin/users.php','/admin/tarif.php','/admin/area.php','/admin/kendaraan.php','/admin/log.php','/admin/backup.php']) {
    await page.goto(BASE + p, { waitUntil: 'networkidle0' });
    const t = await page.evaluate(() => document.documentElement.getAttribute('data-theme'));
    const r = await page.evaluate(() => { const el=document.querySelector('.content'); return el?getComputedStyle(el).backgroundColor:'no-content'; });
    out.push(`dark ${p} theme=${t} contentBg=${r}`);
  }
  await ctx.close();

  // --- PETUGAS: struk (receipt) ---
  const ctx2 = await browser.createBrowserContext();
  const p2 = await ctx2.newPage();
  await p2.setViewport({ width: 900, height: 1200 });
  await p2.goto(BASE + '/auth/login.php', { waitUntil: 'domcontentloaded' });
  await p2.waitForSelector('#username');
  await p2.type('#username','petugas1'); await p2.type('#password','123456');
  await Promise.all([p2.waitForNavigation({waitUntil:'networkidle0'}), p2.click('button[type=submit]')]);
  // goto transaksi to see struk link
  await p2.goto(BASE + '/petugas/transaksi.php', { waitUntil: 'networkidle0' });
  const links = await p2.evaluate(() => [...document.querySelectorAll('a')].map(a=>a.getAttribute('href')).filter(h=>h && h.includes('struk')));
  out.push('struk links: ' + JSON.stringify(links));
  if (links.length) {
    const href = links[0].startsWith('http') ? links[0] : (new URL(links[0], BASE + '/petugas/transaksi.php')).href;
    await p2.goto(href, { waitUntil: 'networkidle0' });
    const rec = await p2.evaluate(() => {
      const o = {};
      o.receipt = !!document.querySelector('.receipt');
      o.receiptBg = document.querySelector('.receipt') ? getComputedStyle(document.querySelector('.receipt')).backgroundColor : 'none';
      o.brand = document.querySelector('.receipt-brand') ? getComputedStyle(document.querySelector('.receipt-brand')).color : 'none';
      o.h1 = document.querySelector('.receipt h1') ? getComputedStyle(document.querySelector('.receipt h1')).fontSize : 'none';
      o.ovf = document.documentElement.scrollWidth - window.innerWidth;
      return o;
    });
    out.push('STRUK: ' + JSON.stringify(rec));
    await p2.screenshot({ path: '_shot_struk.png', fullPage: true });
  }
  await ctx2.close();
  await browser.close();
  console.log(out.join('\n'));
})().catch(e => { console.error('ERR ' + e.message); process.exit(1); });