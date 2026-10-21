const puppeteer = require('puppeteer-core');
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://127.0.0.1:8899/aplikasi-parkir';

function lum(rgb){const [r,g,b]=rgb.map(v=>{v/=255;return v<=0.03928?v/12.92:Math.pow((v+0.055)/1.055,2.4);});return 0.2126*r+0.7152*g+0.0722*b;}
function parseRGB(s){const m=s.match(/\d+(\.\d+)?/g);return m?m.slice(0,3).map(Number):null;}
function contrast(a,b){const l1=lum(parseRGB(a)),l2=lum(parseRGB(b));const [hi,lo]=l1>l2?[l1,l2]:[l2,l1];return (hi+0.05)/(lo+0.05);}

const PAGES = {
  admin: {u:'admin1', pages:['/admin/dashboard.php','/admin/users.php','/admin/tarif.php','/admin/area.php','/admin/kendaraan.php','/admin/log.php','/admin/backup.php']},
  petugas:{u:'petugas1', pages:['/petugas/masuk.php','/petugas/transaksi.php','/petugas/struk.php?id=8']},
  owner: {u:'owner1', pages:['/owner/dashboard.php','/owner/laporan.php']},
};

(async () => {
  const browser = await puppeteer.launch({ executablePath: CHROME, headless:'new', args:['--no-sandbox','--disable-dev-shm-usage'] });
  const bad = [];
  const info = [];
  for (const [role,cfg] of Object.entries(PAGES)) {
    const ctx = await browser.createBrowserContext();
    const page = await ctx.newPage();
    await page.setViewport({width:1440,height:1000});
    await page.goto(BASE+'/auth/login.php',{waitUntil:'domcontentloaded'});
    await page.waitForSelector('#username');
    await page.type('#username',cfg.u); await page.type('#password','123456');
    await Promise.all([page.waitForNavigation({waitUntil:'networkidle0'}), page.click('button[type=submit]')]);
    for (const p of cfg.pages) {
      await page.goto(BASE+p,{waitUntil:'networkidle0'});
      const res = await page.evaluate(() => {
        const out=[];
        const bgOf = (el) => {
          let n = el;
          while (n && n !== document.documentElement) {
            const c = getComputedStyle(n).backgroundColor;
            if (c && c!=='rgba(0, 0, 0, 0)' && c!=='transparent') return c;
            n = n.parentElement;
          }
          return getComputedStyle(document.body).backgroundColor;
        };
        document.querySelectorAll('a,button,th,td,label,p,h1,h2,h3,span,strong,small,li,dt,dd').forEach(el=>{
          if (!el.textContent.trim()) return;
          if (el.children.length && [...el.childNodes].some(n=>n.nodeType===3 && n.textContent.trim())) return;
          const cs = getComputedStyle(el);
          if (cs.visibility==='hidden'||cs.opacity==='0'||cs.display==='none') return;
          const r = el.getBoundingClientRect();
          if (r.width<2||r.height<2) return;
          out.push({ tag: el.tagName+'.'+(el.className||'').toString().split(' ')[0], color: cs.color, bg: bgOf(el), text: el.textContent.trim().slice(0,28) });
        });
        return out;
      });
      for (const e of res) {
        const c = contrast(e.color, e.bg);
        if (c < 3.0) bad.push(`${role}${p} :: ${e.tag} "${e.text}" color=${e.color} bg=${e.bg} ratio=${c.toFixed(2)}`);
      }
      info.push(`${role}${p} -> ${res.length} text nodes`);
    }
    await ctx.close();
  }
  console.log('=== TEXT CONTRAST BELOW 3.0 ===');
  console.log(bad.length ? [...new Set(bad)].join('\n') : 'NONE - all text passes 3:1');
  console.log('\n=== PAGES CHECKED ===');
  console.log(info.join('\n'));
  await browser.close();
})().catch(e=>{console.error('ERR '+e.message);process.exit(1);});