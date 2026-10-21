const puppeteer = require('puppeteer-core');
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://127.0.0.1:8899/aplikasi-parkir';
(async () => {
  const browser = await puppeteer.launch({ executablePath: CHROME, headless:'new', args:['--no-sandbox','--disable-dev-shm-usage'] });
  const ctx = await browser.createBrowserContext();
  const page = await ctx.newPage();
  const errors = [];
  page.on('console', m => { if (m.type()==='error') errors.push('CONSOLE: '+m.text()); });
  page.on('pageerror', e => errors.push('PAGEERROR: '+e.message));
  page.on('requestfailed', r => errors.push('REQFAIL: '+r.url()+' '+(r.failure()&&r.failure().errorText)));

  // 1. Login page
  await page.setViewport({width:1440,height:900});
  await page.goto(BASE+'/auth/login.php',{waitUntil:'networkidle0'});
  console.log('login title:', await page.title());
  console.log('login h1:', await page.$eval('.login-hero h1', e=>e.textContent.trim()));

  // 2. Theme toggle
  const before = await page.evaluate(()=>document.documentElement.dataset.theme||'light');
  await page.click('.theme-toggle');
  const after = await page.evaluate(()=>document.documentElement.dataset.theme||'light');
  const label = await page.$eval('.theme-toggle', e=>e.textContent.trim());
  console.log('theme toggle:', before, '->', after, '| label:', label);
  await page.click('.theme-toggle');
  console.log('theme back:', await page.evaluate(()=>document.documentElement.dataset.theme||'light'));

  // 3. Login as admin
  await page.type('#username','admin1'); await page.type('#password','123456');
  await Promise.all([page.waitForNavigation({waitUntil:'networkidle0'}), page.click('button[type=submit]')]);
  console.log('after login url:', page.url());
  console.log('sidebar links:', await page.$$eval('.sidebar a', els=>els.map(e=>e.textContent.trim()).join(' | ')));
  console.log('active nav:', await page.$eval('.sidebar a.active', e=>e.textContent.trim()).catch(()=>'NONE'));

  // 4. Owner dashboard "Lihat Laporan" link contrast
  const ctx2 = await browser.createBrowserContext();
  const p2 = await ctx2.newPage();
  await p2.setViewport({width:1440,height:900});
  await p2.goto(BASE+'/auth/login.php',{waitUntil:'domcontentloaded'});
  await p2.waitForSelector('#username');
  await p2.type('#username','owner1'); await p2.type('#password','123456');
  await Promise.all([p2.waitForNavigation({waitUntil:'networkidle0'}), p2.click('button[type=submit]')]);
  const btn = await p2.evaluate(()=>{
    const el = document.querySelector('a.crud-button.crud-primary');
    if(!el) return 'MISSING';
    const cs = getComputedStyle(el);
    return { text: el.textContent.trim(), color: cs.color, bg: cs.backgroundColor };
  });
  console.log('owner Lihat Laporan btn:', JSON.stringify(btn));

  // 5. Struk ticket box
  const ctx3 = await browser.createBrowserContext();
  const p3 = await ctx3.newPage();
  await p3.setViewport({width:900,height:1300});
  await p3.goto(BASE+'/auth/login.php',{waitUntil:'domcontentloaded'});
  await p3.waitForSelector('#username');
  await p3.type('#username','petugas1'); await p3.type('#password','123456');
  await Promise.all([p3.waitForNavigation({waitUntil:'networkidle0'}), p3.click('button[type=submit]')]);
  await p3.goto(BASE+'/petugas/struk.php?id=8',{waitUntil:'networkidle0'});
  const ticket = await p3.evaluate(()=>{
    const t = document.querySelector('.receipt-ticket');
    const l = document.querySelector('.receipt-label');
    const s = document.querySelector('.receipt-ticket .receipt-status');
    const g = e => e ? { color:getComputedStyle(e).color, bg:getComputedStyle(e).backgroundColor } : 'MISSING';
    return { ticket:g(t), label:g(l), status:g(s) };
  });
  console.log('struk ticket:', JSON.stringify(ticket));

  // 6. Checkbox size on mobile
  await p3.setViewport({width:390,height:844});
  await p3.goto(BASE+'/admin/users.php',{waitUntil:'networkidle0'}).catch(()=>{});
  const ctx4 = await browser.createBrowserContext();
  const p4 = await ctx4.newPage();
  await p4.setViewport({width:390,height:844});
  await p4.goto(BASE+'/auth/login.php',{waitUntil:'domcontentloaded'});
  await p4.waitForSelector('#username');
  await p4.type('#username','admin1'); await p4.type('#password','123456');
  await Promise.all([p4.waitForNavigation({waitUntil:'networkidle0'}), p4.click('button[type=submit]')]);
  await p4.goto(BASE+'/admin/users.php',{waitUntil:'networkidle0'});
  const cb = await p4.evaluate(()=>{ const c=document.querySelector('input[type=checkbox]'); const b=c.getBoundingClientRect(); return {w:Math.round(b.width),h:Math.round(b.height)}; });
  console.log('checkbox @390px:', JSON.stringify(cb));

  console.log('\n=== JS ERRORS ===');
  console.log(errors.length ? [...new Set(errors)].join('\n') : 'NONE');
  await browser.close();
})().catch(e=>{console.error('ERR '+e.message);process.exit(1);});