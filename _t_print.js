const puppeteer = require('puppeteer-core');
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://127.0.0.1:8899/aplikasi-parkir';
(async () => {
  const browser = await puppeteer.launch({ executablePath: CHROME, headless:'new', args:['--no-sandbox','--disable-dev-shm-usage'] });
  const out = [];
  const ctx = await browser.createBrowserContext();
  const page = await ctx.newPage();
  await page.setViewport({ width: 900, height: 1300 });
  await page.goto(BASE + '/auth/login.php', { waitUntil:'domcontentloaded' });
  await page.waitForSelector('#username');
  await page.type('#username','petugas1'); await page.type('#password','123456');
  await Promise.all([page.waitForNavigation({waitUntil:'networkidle0'}), page.click('button[type=submit]')]);
  await page.goto(BASE + '/petugas/struk.php?id=8', { waitUntil:'networkidle0' });

  await page.emulateMediaType('print');
  const pr = await page.evaluate(() => {
    const g=(s)=>{const e=document.querySelector(s);return e?getComputedStyle(e):null;};
    const o = {};
    const np = g('.no-print'); o.noPrintDisplay = np ? np.display : 'MISSING';
    const tb = g('.topbar'); o.topbarDisplay = tb ? tb.display : 'MISSING';
    const sb = g('.sidebar'); o.sidebarDisplay = sb ? sb.display : 'MISSING';
    const ft = g('.footer'); o.footerDisplay = ft ? ft.display : 'MISSING';
    const rc = g('.receipt'); o.receiptMaxW = rc ? rc.maxWidth : 'MISSING';
    o.receiptShadow = rc ? rc.boxShadow : '';
    const rh = g('.receipt-header'); o.headerBg = rh ? rh.backgroundImage.slice(0,40) : '';
    o.bodyBg = getComputedStyle(document.body).backgroundColor;
    o.receiptBg = rc ? rc.backgroundColor : '';
    return o;
  });
  out.push('PRINT struk: ' + JSON.stringify(pr));
  await page.pdf({ path:'_print_struk.pdf', format:'A4', printBackground:true });
  out.push('PDF generated');

  // Print preview for laporan (owner) - has table + filters
  await ctx.close();
  const ctx2 = await browser.createBrowserContext();
  const p2 = await ctx2.newPage();
  await p2.setViewport({ width: 1280, height: 900 });
  await p2.goto(BASE + '/auth/login.php', { waitUntil:'domcontentloaded' });
  await p2.waitForSelector('#username');
  await p2.type('#username','owner1'); await p2.type('#password','123456');
  await Promise.all([p2.waitForNavigation({waitUntil:'networkidle0'}), p2.click('button[type=submit]')]);
  await p2.goto(BASE + '/owner/laporan.php', { waitUntil:'networkidle0' });
  const lap = await p2.evaluate(() => {
    const g=(s,p)=>{const e=document.querySelector(s);return e?getComputedStyle(e)[p]:'MISSING';};
    return { filters: !!document.querySelector('.report-filters'), table: !!document.querySelector('.report-results'),
             filtersBg: g('.report-filters','backgroundColor'), thBg: g('th','backgroundColor'),
             ovf: document.documentElement.scrollWidth - window.innerWidth };
  });
  out.push('LAPORAN: ' + JSON.stringify(lap));
  await p2.emulateMediaType('print');
  const lapP = await p2.evaluate(() => {
    const g=(s)=>{const e=document.querySelector(s);return e?getComputedStyle(e):null;};
    const f = g('.report-filters'), t = g('.topbar'), tb = g('th');
    return { filtersDisplay: f?f.display:'MISSING', topbarDisplay: t?t.display:'MISSING', thBg: tb?tb.backgroundColor:'' };
  });
  out.push('PRINT laporan: ' + JSON.stringify(lapP));
  await p2.pdf({ path:'_print_laporan.pdf', format:'A4', landscape:true, printBackground:true });
  out.push('PDF laporan generated');
  await ctx2.close();
  await browser.close();
  console.log(out.join('\n'));
})().catch(e=>{console.error('ERR '+e.message);process.exit(1);});