#!/usr/bin/env node
/**
 * Tarayıcıda giriş yapıp Araçlar sayfasından örnek araç ekler.
 * Çalıştırma: npx playwright install chromium && node scripts/browser-test-arac-ekle.mjs
 */
import { chromium } from 'playwright';

const BASE = 'https://depotakip-v1.test';
const EMAIL = 'erkanulker0@gmail.com';
const PASSWORD = 'password';
const PLATE = '34 TEST OTO 2026';
const MODEL_YEAR = '2026';

async function main() {
  let browser;
  try {
    browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await context.newPage();
    page.setDefaultTimeout(20000);

    console.log('1) Giriş sayfasına gidiliyor...');
    await page.goto(BASE + '/giris', { waitUntil: 'load', timeout: 20000 });

    console.log('2) Giriş yapılıyor...');
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL(/genel-bakis|araclar|musteri/, { timeout: 10000 }).catch(() => {});

    console.log('3) Araçlar sayfasına gidiliyor...');
    await page.goto(BASE + '/araclar', { waitUntil: 'load', timeout: 20000 });

    const title = await page.title();
    if (title.includes('Giriş')) {
      console.error('Hata: Giriş yapılamadı, hâlâ giriş sayfasındayız.');
      process.exit(1);
    }
    console.log('   Sayfa başlığı:', title);

    console.log('4) Araç Ekle butonuna tıklanıyor...');
    await page.click('button:has-text("Araç Ekle")');
    await page.waitForSelector('#add_plate', { state: 'visible', timeout: 5000 });

    console.log('5) Form dolduruluyor...');
    await page.fill('#add_plate', PLATE);
    await page.fill('input[name="model_year"]', String(MODEL_YEAR));
    await page.fill('input[name="cargo_volume_m3"]', '12');
    await page.fill('input[name="kasko_date"]', '2026-06-01');
    await page.fill('input[name="inspection_date"]', '2026-12-01');

    console.log('6) Form gönderiliyor...');
    await page.click('form[action="/araclar/ekle"] button[type="submit"]');
    // 303 redirect → GET /araclar; önce yönlendirmeyi bekle, sonra başarı mesajı veya plaka
    await page.waitForURL(url => url.pathname === '/araclar', { timeout: 15000 }).catch(() => {});
    await page.waitForLoadState('domcontentloaded');
    await page.waitForSelector('body', { state: 'attached', timeout: 5000 });
    // Sayfa içeriği görünene kadar bekle (başarı mesajı veya tablo/plaka)
    await Promise.race([
      page.getByText('Araç eklendi').first().waitFor({ state: 'visible', timeout: 10000 }),
      page.getByText(PLATE).first().waitFor({ state: 'visible', timeout: 10000 }),
      page.locator('table').first().waitFor({ state: 'visible', timeout: 10000 }),
    ]).catch(() => {});

    const body = (await page.textContent('body')) || '';
    const success = body.includes('Araç eklendi');
    const plateInTable = body.includes(PLATE);

    if (success) console.log('   OK: "Araç eklendi" mesajı görüldü.');
    if (plateInTable) console.log('   OK: Plaka tabloda görünüyor.');
    if (!success && !plateInTable) {
      const url = page.url();
      console.error('   Hata: Başarı mesajı veya plaka bulunamadı.');
      console.error('   URL:', url);
      if (url.endsWith('/araclar/ekle') && !body.trim()) {
        console.error('   Not: Form gönderildi ama sayfa /araclar\'a yönlenmedi ve body boş.');
        console.error('   Sunucuda POST /araclar/ekle işlendikten sonra 303 ile Location: /araclar dönmeli.');
      }
      console.error('   Body snippet:', body.slice(0, 500) || '(boş)');
      try {
        await page.screenshot({ path: 'scripts/browser-test-arac-ekle-fail.png' });
        console.error('   Ekran görüntüsü: scripts/browser-test-arac-ekle-fail.png');
      } catch (_) {}
      process.exit(1);
    }

    console.log('7) Test başarılı.');
  } catch (e) {
    console.error('Hata:', e.message);
    process.exit(1);
  } finally {
    if (browser) await browser.close();
  }
}

main();
