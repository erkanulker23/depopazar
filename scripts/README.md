# DepoPazar – Scripts

Bu klasör yerel geliştirme ve sunucu dışı ortamlarda kullanılan script’leri içerir.

## Proje kökünden kullanım

- **Servisleri başlat:** `./run-all.sh` (kökteki wrapper `scripts/run-all.sh` çağırır)
- **Servisleri durdur:** `./stop-all.sh`

## Scripts klasörü içeriği

| Script | Açıklama |
|--------|----------|
| `run-all.sh` | Docker + Backend + Frontend’i başlatır |
| `stop-all.sh` | Backend ve Frontend process’lerini durdurur |
| `fix-valet.sh` | Valet (Laravel) yerel domain ayarlarını düzeltir |
| `setup-valet.sh` | Valet kurulumu |
| `restart-valet.sh` | Valet’i yeniden başlatır |
| `test-connection.sh` | Yerel API ve frontend bağlantısını test eder |

## Yerel Nginx/Valet/Laragon

`valet*.conf`, `laragon.conf`, `nginx.conf` dosyaları **sadece yerel ortam** (Valet/Laragon) için referanstır. Sunucuda Nginx konfigürasyonu Forge vb. panel üzerinden yönetilir.

## Path alias (@/)

Backend ve frontend’te `@/` ile `src/` altındaki modüllere import yapılabilir (tsconfig path alias ile yapılandırılmıştır).
