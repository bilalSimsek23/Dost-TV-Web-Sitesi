const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const ARTIFACT_DIR = '/Users/mac/.gemini/antigravity-ide/brain/73ac75ff-b9af-460e-b085-86febe2f6489';
const CHROME_PATH = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const REAL_IMAGE_PATH = '/tmp/dosttv_test_duyuru_afis.jpg';

const sleep = ms => new Promise(r => setTimeout(r, ms));

async function main() {
  console.log('Starting Google Chrome browser test...');

  const browser = await puppeteer.launch({
    executablePath: CHROME_PATH,
    headless: true,
    defaultViewport: { width: 1440, height: 900 }
  });

  const page = await browser.newPage();

  // 1. LOGIN
  console.log('Navigating to login page...');
  await page.goto('http://127.0.0.1:8000/admin/login', { waitUntil: 'networkidle2' });

  const emailInput = await page.$('input[type="email"]');
  if (emailInput) {
    await emailInput.type('admin@dosttv.com');
    await (await page.$('input[type="password"]')).type('password');
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle2' }),
      (await page.$('button[type="submit"]')).click()
    ]);
  }

  console.log('Logged in. Current URL:', page.url());

  // 2. STEP 1: Open /admin/announcements/create
  console.log('Navigating to /admin/announcements/create...');
  await page.goto('http://127.0.0.1:8000/admin/announcements/create', { waitUntil: 'networkidle2' });
  await sleep(1500);

  const step1Path = path.join(ARTIFACT_DIR, 'step1_create_page.png');
  await page.screenshot({ path: step1Path, fullPage: false });
  console.log('Step 1 screenshot saved:', step1Path);

  // 3. STEP 2: Fill form fields
  // Fill title
  const titleInput = await page.$('input[id*="title"]');
  if (titleInput) {
    await titleInput.type('Canlı Tarayıcı Görsel Yükleme Duyurusu');
  }

  // Select Announcement Type (Duyuru Türü)
  const selectBtn = await page.$('button[id*="announcement_type_id"]');
  if (selectBtn) {
    await selectBtn.click();
    await sleep(500);
    // Click first option in dropdown listbox
    await page.evaluate(() => {
      const options = document.querySelectorAll('li[role="option"], [id*="fi-select-input-dropdown"] [role="option"], .fi-select-input-option');
      if (options.length > 0) {
        options[0].click();
      }
    });
    await sleep(500);
  }

  // Upload file to file input
  const fileInput = await page.$('input[type="file"]');
  if (fileInput) {
    console.log('Uploading real JPG file:', REAL_IMAGE_PATH);
    await fileInput.uploadFile(REAL_IMAGE_PATH);
    // Wait for FilePond upload to finish and preview to render
    await sleep(4500);
  } else {
    console.error('File input element not found!');
  }

  const step2Path = path.join(ARTIFACT_DIR, 'step2_image_preview.png');
  await page.screenshot({ path: step2Path, fullPage: false });
  console.log('Step 2 screenshot saved:', step2Path);

  // 4. STEP 3: Click Create button
  console.log('Submitting announcement form...');
  const createClicked = await page.evaluate(() => {
    const btns = Array.from(document.querySelectorAll('button'));
    const target = btns.find(b => b.innerText.trim() === 'Create' || b.innerText.trim() === 'Oluştur' || b.innerText.trim() === 'Kaydet');
    if (target) {
      target.click();
      return true;
    }
    return false;
  });

  console.log('Form create button clicked:', createClicked);
  await sleep(6000);

  const step3Path = path.join(ARTIFACT_DIR, 'step3_form_saved.png');
  await page.screenshot({ path: step3Path, fullPage: false });
  console.log('Step 3 screenshot saved:', step3Path);

  // 5. Query latest created record from DB
  const phpScript = `
  require 'vendor/autoload.php';
  \\$app = require_once 'bootstrap/app.php';
  \\$kernel = \\$app->make(Illuminate\\Contracts\\Console\\Kernel::class);
  \\$kernel->bootstrap();
  \\$latest = \\App\\Models\\Announcement::latest('id')->first();
  echo json_encode([
    'id' => \\$latest->id,
    'title' => \\$latest->title,
    'image' => \\$latest->image,
    'is_active' => \\$latest->is_active
  ]);
  `;

  const dbOutput = execSync(`php -r "${phpScript}"`, { cwd: '/Users/mac/Dost TV Web Site' }).toString();
  console.log('Database record query:', dbOutput);
  const dbData = JSON.parse(dbOutput);

  // 6. STEP 4: Open direct storage image URL
  const imageUrl = `http://127.0.0.1:8000/storage/${dbData.image}`;
  console.log('Navigating to direct storage image URL:', imageUrl);
  await page.goto(imageUrl, { waitUntil: 'networkidle2' });
  await sleep(1500);

  const step4Path = path.join(ARTIFACT_DIR, 'step4_direct_storage_url.png');
  await page.screenshot({ path: step4Path, fullPage: false });
  console.log('Step 4 screenshot saved:', step4Path);

  // 7. STEP 5: Public homepage popup test
  console.log('Navigating to public homepage to verify popup modal...');
  await page.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle2' });
  // Clear sessionStorage to ensure modal opens
  await page.evaluate(() => sessionStorage.clear());
  await page.reload({ waitUntil: 'networkidle2' });
  await sleep(2500);

  const step5Path = path.join(ARTIFACT_DIR, 'step5_public_popup.png');
  await page.screenshot({ path: step5Path, fullPage: false });
  console.log('Step 5 screenshot saved:', step5Path);

  await browser.close();
  console.log('Browser test script finished successfully!');
}

main().catch(err => {
  console.error('Error running browser test script:', err);
  process.exit(1);
});
