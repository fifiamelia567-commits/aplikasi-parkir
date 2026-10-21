const puppeteer = require('puppeteer-core');
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://127.0.0.1:8899/aplikasi-parkir';

(async () => {
  const browser = await puppeteer.launch({
    executablePath: CHROME, headless: 'new',
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });
  const page = await browser.newPage();
  await page.setViewport({ width: 1440, height: 1000, deviceScaleFactor: 1 });

  // Login page
  await page.goto(BASE + '/auth/login.php', { waitUntil: 'networkidle0' });
  await page.screenshot({ path: '_shot_login.png', fullPage: true });
  console.log('login OK');

  // Login as admin
  await page.type('#username', 'admin1');
  await page.type('#password', '123456');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle0' }),
    page.click('.login-submit'),
  ]);
  console.log('after login URL: ' + page.url());
  await page.screenshot({ path: '_shot_dashboard.png', fullPage: true });

  // Dark mode
  await page.click('.theme-toggle');
  await new Promise(r => setTimeout(r, 400));
  await page.screenshot({ path: '_shot_dashboard_dark.png', fullPage: true });
  await page.click('.theme-toggle');
  await new Promise(r => setTimeout(r, 300));

  // Other pages
  for (const [path, name] of [
    ['/admin/kendaraan.php', 'kendaraan'],
    ['/admin/backup.php', 'backup'],
    ['/owner/laporan.php', 'laporan'],
  ]) {
    await page.goto(BASE + path, { waitUntil: 'networkidle0' });
    await page.screenshot({ path: '_shot_' + name + '.png', fullPage: true });
    console.log(name + ' OK');
  }
  await browser.close();
  console.log('DONE');
})().catch(e => { console.error('ERR: ' + e.message); process.exit(1); });