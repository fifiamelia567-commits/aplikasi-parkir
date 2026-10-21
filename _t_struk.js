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

  // STRUK
  await page.goto(BASE + '/petugas/struk.php?id=8', { waitUntil:'networkidle0' });
  const rec = await page.evaluate(() => {
    const o = {};
    o.exists = !!document.querySelector('.receipt');
    const g=(s,p)=>{const e=document.querySelector(s);return e?getComputedStyle(e)[p]:'MISSING';};
    o.receiptBg = g('.receipt','backgroundColor');
    o.receiptMaxW = g('.receipt','maxWidth');
    o.brandColor = g('.receipt-brand','color');
    o.brandMarkBg = g('.receipt-brand-mark','backgroundImage').slice(0,50);
    o.h1Size = g('.receipt h1','fontSize');
    o.h1Color = g('.receipt h1','color');
    o.totalBg = g('.receipt-total','backgroundColor');
    o.totalColor = g('.receipt-total','color');
    o.barcodeH = g('.receipt-barcode-svg','height');
    o.actionsBtn = g('.receipt-actions button','backgroundImage').slice(0,50);
    o.ovf = document.documentElement.scrollWidth - window.innerWidth;
    o.sections = document.querySelectorAll('.receipt-section').length;
    o.details = document.querySelectorAll('.receipt-details > div').length;
    o.svgBars = document.querySelectorAll('.receipt-barcode-svg rect').length;
    return o;
  });
  out.push('STRUK: ' + JSON.stringify(rec, null, 1));
  await page.screenshot({ path:'_shot_struk.png', fullPage:true });

  // PRINT preview simulation
  await page.emulateMediaType('print');
  const pr = await page.evaluate(() => {
    const g=(s,p)=>{const e=document.querySelector(s);return e?getComputedStyle(e)[p]:'MISSING';};
    const np = getComputedStyle(document.querySelector('.no-print'), 'display');
    const receipt = document.querySelector('.receipt');
    const r = receipt ? getComputedStyle(receipt) : null;
    return { noPrintDisplay: np, receiptBoxShadow: r?r.boxShadow:'none', receiptBorder: r?r.border:'none',
             bodyBg: getComputedStyle(document.body).backgroundColor };
  });
  out.push('PRINT: ' + JSON.stringify(pr));
  await page.emulateMediaType(null);
  await ctx.close();
  await browser.close();
  console.log(out.join('\n'));
})().catch(e=>{console.error('ERR '+e.message);process.exit(1);});