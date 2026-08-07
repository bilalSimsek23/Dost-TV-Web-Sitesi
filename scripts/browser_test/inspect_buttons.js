const puppeteer = require('puppeteer-core');

async function main() {
  const browser = await puppeteer.launch({
    executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    headless: true
  });
  const page = await browser.newPage();
  await page.goto('http://127.0.0.1:8000/admin/login', { waitUntil: 'networkidle2' });
  const emailInput = await page.$('input[type="email"]');
  if (emailInput) {
    await emailInput.type('admin@dosttv.com');
    await (await page.$('input[type="password"]')).type('password');
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2' }), (await page.$('button[type="submit"]')).click()]);
  }
  await page.goto('http://127.0.0.1:8000/admin/announcements/create', { waitUntil: 'networkidle2' });
  const buttons = await page.evaluate(() => {
    return Array.from(document.querySelectorAll('button, a.fi-btn')).map(b => ({
      text: b.innerText.trim(),
      type: b.getAttribute('type'),
      wireClick: b.getAttribute('wire:click'),
      outerHTML: b.outerHTML.substring(0, 200)
    }));
  });
  console.log('Buttons on create page:', JSON.stringify(buttons, null, 2));
  await browser.close();
}

main();
