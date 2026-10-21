const puppeteer = require('puppeteer-core');
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://127.0.0.1:8899/aplikasi-parkir';

(async () => {
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new',
    args: ['--no-sandbox', '--disable-dev-shm-usage'] });
  const page = await browser.newPage();
  await page.setViewport({ width: 1440, height: 1000 });
  await page.goto(BASE + '/auth/login.php', { waitUntil: 'networkidle0' });

  const report = await page.evaluate(() => {
    const out = {};
    const g = (sel, prop) => {
      const el = document.querySelector(sel);
      if (!el) return 'MISSING';
      return getComputedStyle(el)[prop];
    };
    out.bodyBg = g('body', 'backgroundColor');
    out.heroBg = g('.login-hero', 'backgroundImage').slice(0, 80);
    out.heroColor = g('.login-hero', 'color');
    out.markBg = g('.login-mark', 'backgroundImage').slice(0, 60);
    out.submitBg = g('.login-submit', 'backgroundImage').slice(0, 60);
    out.submitColor = g('.login-submit', 'color');
    out.inputBorder = g('.field input', 'borderTopColor');
    out.inputFocusRing = 'n/a';
    out.availItemBg = g('.parking-availability-item', 'backgroundColor');
    out.availCountColor = g('.parking-availability-count strong', 'color');
    out.layoutCols = g('.login-layout', 'gridTemplateColumns');
    out.docWidth = document.documentElement.scrollWidth;
    out.winWidth = window.innerWidth;
    out.overflowX = document.documentElement.scrollWidth > window.innerWidth;
    out.avatarIcon = g('.parking-availability-icon svg', 'stroke');
    return out;
  });
  console.log('=== LOGIN PAGE ===');
  console.log(JSON.stringify(report, null, 2));

  // Check every SVG stroke color used
  const svgColors = await page.evaluate(() => {
    const set = new Set();
    document.querySelectorAll('svg').forEach(s => {
      const cs = getComputedStyle(s);
      set.add(cs.stroke + ' / ' + cs.fill);
    });
    return [...set];
  });
  console.log('svg colors: ' + JSON.stringify(svgColors));
  await browser.close();
})().catch(e => { console.error('ERR ' + e.message); process.exit(1); });