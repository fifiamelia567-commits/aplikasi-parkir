const puppeteer = require('puppeteer-core');
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://127.0.0.1:8899/aplikasi-parkir';
const PAGES = {
  admin: {u:'admin1', pages:['/admin/dashboard.php','/admin/users.php','/admin/tarif.php','/admin/area.php','/admin/kendaraan.php','/admin/log.php','/admin/backup.php']},
  petugas:{u:'petugas1', pages:['/petugas/masuk.php','/petugas/transaksi.php','/petugas/struk.php?id=8']},
  owner: {u:'owner1', pages:['/owner/dashboard.php','/owner/laporan.php']},
};
function lum(rgb){const m=rgb.match(/[\d.]+/g).map(Number);const[r,g,b]=m.slice(0,3).map(v=>{v/=255;return v<=0.03928?v/12.92:Math.pow((v+0.055)/1.055,2.4);});return 0.2126*r+0.7152*g+0.0722*b;}
function contrast(a,b){const l1=lum(a),l2=lum(b);const[hi,lo]=l1>l2?[l1,l2]:[l2,l1];return (hi+0.05)/(lo+0.05);}
(async () => {
  const browser = await puppeteer.launch({ executablePath: CHROME, headless:'new', args:['--no-sandbox','--disable-dev-shm-usage'] });
  const bad = [];
  for (const theme of ['light','dark']) {
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
        await page.evaluate((t)=>{ document.documentElement.setAttribute('data-theme',t); localStorage.setItem('parkirku-theme',t); }, theme);
        await page.reload({waitUntil:'networkidle0'});
        const res = await page.evaluate(() => {
          const parse = s => { const m = s.match(/[\d.]+/g); return m ? { r:+m[0], g:+m[1], b:+m[2], a: m[3]!==undefined?+m[3]:1 } : null; };
          const over = (fg,bg) => ({ r: fg.r*fg.a+bg.r*(1-fg.a), g: fg.g*fg.a+bg.g*(1-fg.a), b: fg.b*fg.a+bg.b*(1-fg.a), a:1 });
          const effBg = (el) => {
            const layers=[]; let n=el;
            while(n && n!==document.documentElement){ const cs=getComputedStyle(n);
              if(cs.backgroundImage && cs.backgroundImage!=='none') return null;
              const c=parse(cs.backgroundColor); if(c&&c.a>0){layers.push(c); if(c.a===1)break;} n=n.parentElement; }
            layers.push({r:255,g:255,b:255,a:1});
            let base=layers[layers.length-1];
            for(let i=layers.length-2;i>=0;i--) base=over(layers[i],base);
            return base;
          };
          const out=[];
          document.querySelectorAll('a,button,th,td,label,p,h1,h2,h3,span,strong,small,li,dt,dd,div').forEach(el=>{
            const own=[...el.childNodes].some(n=>n.nodeType===3&&n.textContent.trim()); if(!own) return;
            const cs=getComputedStyle(el);
            if(cs.visibility==='hidden'||cs.opacity==='0'||cs.display==='none') return;
            const r=el.getBoundingClientRect(); if(r.width<2||r.height<2) return;
            const bg=effBg(el); if(!bg) return;
            const fg=over(parse(cs.color),bg);
            out.push({tag:el.tagName+'.'+((el.className||'').toString().split(' ')[0]), color:cs.color, bg:`rgb(${Math.round(bg.r)}, ${Math.round(bg.g)}, ${Math.round(bg.b)})`, text:el.textContent.trim().slice(0,30)});
          });
          return out;
        });
        for (const e of res) {
          const c = contrast(e.color, e.bg);
          if (c < 3.0) bad.push(`[${theme}] ${role}${p} :: ${e.tag} "${e.text}" fg=${e.color} bg=${e.bg} ratio=${c.toFixed(2)}`);
        }
      }
      await ctx.close();
    }
  }
  console.log(bad.length ? [...new Set(bad)].join('\n') : 'NONE - all light+dark pass 3:1');
  await browser.close();
})().catch(e=>{console.error('ERR '+e.message);process.exit(1);});