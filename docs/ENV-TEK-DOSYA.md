# Tek .env Kullanımı

DepoPazar projesi **tek bir .env dosyası** kullanır: **proje kökündeki** `.env` (yani `backend/` ve `frontend/` klasörlerinin bulunduğu dizin).

## Hangi dosya?

- **Lokal:** Proje kökünde `.env` (depopazar/.env)
- **Forge:** `/home/forge/SITE_DOMAIN/.env` (örn. general.awapanel.com için `/home/forge/general.awapanel.com/.env`)

Backend, migration script’i ve PM2 ecosystem hepsi bu tek dosyayı okur. `backend/.env` artık kullanılmaz.

## Forge / sunucu kurulumu (önemli)

1. **Settings → General**
   - **Root directory:** `backend` **olmasın**; **boş bırakın** (veya `/`). Site kökü = proje kökü (backend + frontend’in olduğu dizin).
   - **Web directory:** `frontend/dist` yazın (Forge varsayılanı `/public` değil — projede public yok; React build `frontend/dist` içinde).

2. **Settings → Processes → Background process**
   - Komut: `node backend/dist/main.js`
   - Çalışma dizini: proje kökü (örn. `/home/forge/general.awapanel.com`)

3. **Settings → Environment**  
   Proje kökündeki tek .env buradan düzenlenir.

Bu sayede Forge’da Environment’ı değiştirdiğinizde backend gerçekten bu .env’i kullanır. Process’i yeniden başlattıktan sonra değişiklikler uygulanır (Supervisor “Restart” veya tekrar deploy).

## Örnek .env (proje kökü)

`.env.example` dosyasını proje köküne kopyalayıp `.env` yapın ve değerleri doldurun. Backend ve migration bu dosyayı okur.
