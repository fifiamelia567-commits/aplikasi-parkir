const puppeteer = require('puppeteer-core');
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://127.0.0.1:8899/aplikasi-parkir';
const PAGES = {
  admin: {u:'admin1', pages:['/admin/dashboard.php','/admin/users.php','/admin/tarif.php','/admin/area.php','/admin/kendaraan.php','/admin/log.php','/admin/backup.php']},
  petugas:{u:'petugas1', pages:['/petugas/masuk.php','/petugas/transaksi.php','/petugas/struk.php?id=8']},
  owner: {u:'owner1', pages:['/owner/dashboard.php','/owner/laporan.php']},
};
const SIZES = [[360,740,'iPhone SE'],[390,844,'iPhone 12'],[768,1024,'iPad'],[1024,768,'Tablet L'],[1440,900,'Desktop']];
(async () => {
  const browser = await puppeteer.launch({ executablePath: CHROME, headless:'new', args:['--no-sandbox','--disable-dev-shm-usage'] });
  const issues = [];
  for (const [role,cfg] of Object.entries(PAGES)) {
    const ctx = await browser.createBrowserContext();
    const page = await ctx.newPage();
    await page.setViewport({width:1440,height:900});
    await page.goto(BASE+'/auth/login.php',{waitUntil:'domcontentloaded'});
    await page.waitForSelector('#username');
    await page.type('#username',cfg.u); await page.type('#password','123456');
    await Promise.all([page.waitForNavigation({waitUntil:'networkidle0'}), page.click('button[type=submit]')]);
    for (const p of cfg.pages) {
      for (const [w,h,label] of SIZES) {
        await page.setViewport({width:w,height:h});
        await page.goto(BASE+p,{waitUntil:'networkidle0'});
        const r = await page.evaluate(() => {
          const de = document.documentElement;
          const ovf = de.scrollWidth - de.clientWidth;
          const offenders = [];
          if (ovf > 1) {
            document.querySelectorAll('*').forEach(el => {
              const b = el.getBoundingClientRect();
              if (b.width < 1) return;
              if (b.right > de.clientWidth + 1 || b.left < -1) {
                const cs = getComputedStyle(el);
                if (cs.position === 'fixed') return;
                // ignore if inside a scrollable container
                let p = el.parentElement, scrollable = false;
                while (p) { const ps = getComputedStyle(p);
                  if (ps.overflowX === 'auto' || ps.overflowX === 'scroll' || ps.overflow === 'auto' || ps.overflow === 'hidden') { scrollable = true; break; }
                  p = p.parentElement; }
                if (scrollable) return;
                offenders.push(el.tagName + '.' + ((el.className||'').toString().split(' ')[0]) + ' right=' + Math.round(b.right) + ' w=' + Math.round(b.width));
              }
            });
          }
          // touch target check
          const small = [];
          document.querySelectorAll('a,button,input,select,textarea').forEach(el=>{
            const b = el.getBoundingClientRect();
            if (b.width < 1 || b.height < 1) return;
            if (b.height < 24) small.push(el.tagName+'.'+((el.className||'').toString().split(' ')[0])+' h='+Math.round(b.height)+' "'+el.textContent.trim().slice(0,18)+'"');
          });
          return { ovf, offenders: [...new Set(offenders)].slice(0,6), small: [...new Set(small)].slice(0,4) };
        });
        if (r.ovf > 1) issues.push(`OVERFLOW ${role}${p} @${label}(${w}px): ${r.ovf}px | ${r.offenders.join(' ; ')}`);
        if (r.small.length && w <= 390) issues.push(`SMALL-TARGET ${role}${p} @${label}: ${r.small.join(' ; ')}`);
      }
    }
    await ctx.close();
  }
  console.log(issues.length ? issues.join('\n') : 'NONE');
  await browser.close();
})().catch(e=>{console.error('ERR '+e.message);process.exit(1);});