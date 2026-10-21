const puppeteer = require('puppeteer-core');
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://127.0.0.1:8899/aplikasi-parkir';
const ROLES = {
  admin:   { u:'admin1',   pages:['/admin/dashboard.php','/admin/users.php','/admin/tarif.php','/admin/area.php','/admin/kendaraan.php','/admin/log.php','/admin/backup.php'] },
  petugas: { u:'petugas1', pages:['/petugas/masuk.php','/petugas/transaksi.php'] },
  owner:   { u:'owner1',   pages:['/owner/dashboard.php','/owner/laporan.php'] },
};
(async () => {
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox','--disable-dev-shm-usage'] });
  const issues = [];
  const check = async (page, tag) => {
    const res = await page.evaluate(() => {
      const o = { ovf: document.documentElement.scrollWidth - window.innerWidth };
      o.h1direct = !!document.querySelector('.content > h1');
      o.h1any = document.querySelectorAll('h1').length;
      const inv = [];
      document.querySelectorAll('a,button,th,td,label,span,p,h1,h2,h3,.btn,.badge').forEach(el => {
        if (!el.textContent.trim()) return;
        const cs = getComputedStyle(el);
        if (cs.visibility === 'hidden' || cs.opacity === '0') inv.push(el.className+' :: '+el.textContent.trim().slice(0,25));
      });
      o.invisible = inv.slice(0,5);
      const tiny = [];
      document.querySelectorAll('.card,.dashboard-stat,.dashboard-panel,.btn,.badge').forEach(el => {
        const r = el.getBoundingClientRect();
        if (r.width < 2 || r.height < 2) tiny.push(el.className+' '+Math.round(r.width)+'x'+Math.round(r.height));
      });
      o.tiny = tiny.slice(0,5);
      return o;
    });
    if (res.ovf > 2) issues.push(`${tag} OVERFLOW-X ${res.ovf}px`);
    if (res.invisible.length) issues.push(`${tag} INVISIBLE ${JSON.stringify(res.invisible)}`);
    if (res.tiny.length) issues.push(`${tag} TINY ${JSON.stringify(res.tiny)}`);
    if (res.h1any && !res.h1direct) issues.push(`${tag} h1 NOT direct child of .content`);
  };
  for (const [role, cfg] of Object.entries(ROLES)) {
    for (const vp of [{w:1440,h:1000,n:'desktop'},{w:390,h:844,n:'mobile'}]) {
      const ctx = await browser.createBrowserContext();
      const page = await ctx.newPage();
      await page.setViewport({ width: vp.w, height: vp.h });
      await page.goto(BASE + '/auth/login.php', { waitUntil: 'domcontentloaded' });
      await page.waitForSelector('#username', { timeout: 20000 });
      await page.waitForSelector('#password', { timeout: 20000 });
      await page.type('#username', cfg.u);
      await page.type('#password', '123456');
      await Promise.all([ page.waitForNavigation({waitUntil:'networkidle0'}), page.click('button[type=submit]') ]);
      for (const p of cfg.pages) {
        await page.goto(BASE + p, { waitUntil: 'networkidle0' });
        await check(page, `${role}/${vp.n}${p}`);
      }
      await ctx.close();
    }
  }
  console.log(issues.length ? issues.join('\n') : 'NO LAYOUT ISSUES FOUND');
  await browser.close();
})().catch(e => { console.error('ERR ' + e.message); process.exit(1); });