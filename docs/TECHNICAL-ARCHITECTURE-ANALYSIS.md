# DepoPazar – Teknik Mimari Analiz (Senior Yazılım Mimarı Gözüyle)

Bu belge, projenin sistem mimarisi, veritabanı yönetimi, çoklu site stratejisi, hata yönetimi, güvenlik, taşınabilirlik ve teknik borç konularında kapsamlı bir analiz sunar.

---

## 1. Sistem Mimarisi (Architectural Overview)

### 1.1 Kullanılan Tasarım Desenleri

| Desen | Kullanım | Konum |
|-------|----------|--------|
| **Modüler Mimari (Module Pattern)** | NestJS modül yapısı; her domain (auth, companies, contracts, vb.) ayrı modül | `src/modules/*`, `app.module.ts` |
| **Dependency Injection (DI)** | Constructor injection ile servis/repository enjeksiyonu | Tüm `*.service.ts`, controller'lar |
| **Repository Pattern** | TypeORM `Repository<T>` üzerinden veri erişimi; doğrudan SQL yerine entity/query builder | `@InjectRepository(Entity)` kullanan tüm servisler |
| **Guard Pattern** | JWT + Role tabanlı erişim kontrolü | `JwtAuthGuard`, `RolesGuard` |
| **Filter Pattern** | Global exception handling | `AllExceptionsFilter` |
| **Decorator Pattern** | `@UseGuards`, `@Roles()`, `@CurrentUser()`, `@Public()` | Controller metodları |
| **Strategy Pattern** | Passport JWT stratejisi | `JwtStrategy` |
| **Singleton** | NestJS modül sağlayıcıları varsayılan olarak singleton | Tüm `@Injectable()` sınıflar |

**Açıkça kullanılmayan / zayıf olanlar:**

- **Factory Pattern:** Config/DB bağlantısı `useFactory` ile yapılıyor; özel factory sınıfları yok.
- **Unit of Work:** Her servis metodu kendi transaction’ını açmıyor; TypeORM varsayılan auto-commit.
- **CQRS / Event-driven:** Yok; klasik CRUD + servis katmanı.

### 1.2 Katmanlar ve Veri Akışı

```
[Client] → [Nginx] → [Controller] → [Guard] → [Service] → [Repository] → [DB]
                ↓           ↓            ↓
            CORS      ValidationPipe   AllExceptionsFilter
```

**Veri akışı özeti:**

1. **Controller:** HTTP isteği alır; DTO/param/query kullanır; `@CurrentUser()` ile JWT’den user alır; `getCompanyIdForUser(user)` ile şirket kapsamı (multi-tenant) controller veya service’e iletilir.
2. **Service:** İş mantığı; company_id ile filtreleme (örn. `customer.company_id = :companyId`); Repository çağrıları.
3. **Entity:** TypeORM entity’leri; `BaseEntity` (id, created_at, updated_at, deleted_at) kullanımı.
4. **DTO:** Giriş doğrulaması için class-validator; `ValidationPipe` (whitelist, transform) global.

**Örnek akış (Sözleşme listesi):**

```text
GET /api/contracts?page=1&limit=10
  → ContractsController.findAll()
  → JwtAuthGuard, RolesGuard
  → companiesService.getCompanyIdForUser(user)
  → contractsService.findAllPaginated(companyId, params, filters)
  → contractsRepository.createQueryBuilder().where('customer.company_id = :companyId', { companyId })...
  → JSON response
```

**Dikkat:** Company scope bazen controller’da (ensureContractAccess, getCompanyIdForUser), bazen service’e parametre olarak geçiriliyor. Tutarlı bir “tenant context” katmanı (ör. her istekte `companyId`’nin merkezi enjeksiyonu) yok; her modül kendi kontrolünü yapıyor.

---

## 2. Veritabanı ve Kaynak Yönetimi

### 2.1 Veritabanı Bağlantısı Nerede ve Nasıl?

- **Uygulama çalışırken:** `DatabaseModule` içinde, `TypeOrmModule.forRootAsync()` ile.
- **Konfigürasyon:** `getDbConfig(ConfigService)` fonksiyonu `database.module.ts` içinde tanımlı; `ConfigService` ile env değişkenleri okunuyor.
- **Env kaynağı:** `AppModule` içinde `ConfigModule.forRoot({ isGlobal: true, envFilePath: rootEnv })`; `rootEnv` proje kökündeki `.env` (backend’den çalışıyorsa `../.env`).
- **Ek zorlama:** `main.ts` içinde `config({ path: join(projectRoot, '.env'), override: true })` ile `dotenv` doğrudan çağrılıyor; böylece Forge vb. ortam enjeksiyonu yerine proje kökündeki `.env` değerleri geçerli oluyor.

**Özet:**

- Bağlantı: **NestJS uygulama ayağa kalkarken** `DatabaseModule` load edilirken kuruluyor.
- Okuma: Önce `main.ts` dotenv ile `.env` yükleniyor, sonra `ConfigModule` aynı `envFilePath` ile okuyor; tek kaynak proje kökü `.env`.

### 2.2 .env Okuma Mantığı

| Konum | Nasıl okunuyor |
|-------|-----------------|
| **Backend (Nest)** | `AppModule`: `envFilePath: join(projectRoot, '.env')`. `main.ts`: `config({ path: join(projectRoot, '.env'), override: true })`. |
| **Backend (CLI)** | Migration/seed: `src/config/database.config.ts` ve `run-seeds.ts` kendi `dotenv` ile `join(projectRoot, '.env')` kullanıyor. |
| **Frontend (build)** | `vite.config.ts`: `envDir: path.resolve(__dirname, '..')` → proje kökü; build anında `VITE_*` değişkenleri kök `.env`’den alınıyor. |

**Proje kökü tespiti:** `process.cwd().endsWith('backend') ? join(process.cwd(), '..') : process.cwd()`. Yani backend’den çalıştırılıyorsa kök bir üst dizin kabul ediliyor.

### 2.3 Taşıma Sırasında “Veritabanı Bilgilerini Çekme” Darboğazları

1. **Farklı çalışma dizini:** `npm run start:prod` backend dizininden çalışıyorsa `process.cwd()` backend olur, kök `.env` doğru bulunur. Eğer process başka bir dizinden (veya PM2/Forge “cwd” yanlış) başlarsa `.env` yanlış yerde aranır, DB bağlantısı veya CORS/FRONTEND_URL hataları çıkar.
2. **Çift env yükleme:** Hem `main.ts` hem `ConfigModule` .env yüklüyor; `override: true` ile main öncelikli. Farklı ortamlarda (staging/prod) bir yerde env’i değiştirirsen davranışı takip etmek zor.
3. **Migration/Seed CLI:** `database.config.ts` ve seed script’leri ayrı dotenv kullanıyor; `projectRoot` mantığı tekrarlanıyor. CWD farklıysa (ör. CI’da root’tan `npm run migration:run` backend’de çalışıyorsa) yine kök `.env` beklenir; script’ler backend’den çalıştırıldığı varsayılıyor.
4. **Production’da zorunlu alanlar:** `database.module.ts` içinde `requireEnv(configService, 'DB_*')` ile production’da DB_* boş bırakılamıyor. Yeni sunucuda `.env` kopyalanıp doldurulmazsa uygulama başlarken anlamlı hata verir; ancak hata mesajı sadece “Production için DB_HOST .env içinde tanımlanmalıdır” gibi genel bir ifade.
5. **Connection pool:** TypeORM varsayılan pool kullanılıyor; yük altında pool boyutu/limitleri ayrıca konfigüre edilmediği için ileride ölçekte darboğaz çıkabilir.

---

## 3. Çoklu Site (Multi-tenancy) ve Subdomain Stratejisi

### 3.1 Mevcut Durum: Kod vs Altyapı

**Kod tarafında subdomain/domain bazlı tenant yok.** Yani:

- İstekte gelen host/subdomain (örn. `general.awapanel.com` vs `depo.awapanel.com`) **hiçbir yerde okunmuyor**.
- Tenant ayrımı **JWT içindeki `company_id` ve kullanıcı rolü** ile yapılıyor: her kullanıcı bir şirkete bağlı; super_admin hariç veriler `company_id` ile filtreleniyor.
- “General” ve “depo” gibi siteler, **ayrı kurulumlar** olarak çalışıyor: her biri için ayrı sunucu/site dizini, ayrı `.env`, ayrı veritabanı (DB_DATABASE farklı), ayrı Nginx + process.

Yani çoklu site **tamamen altyapı (Nginx + farklı portlar / farklı site root’ları)** ile yönetiliyor; uygulama kodu tek tenant (tek DB) gibi çalışıyor, her deploy “bir şirket / bir site” varsayıyor.

### 3.2 Üçüncü Bir Subdomain Eklemek İsteyince

Kodda **subdomain’e özel değişiklik yapmanız gerekmez.** Yapılacaklar tamamen altyapı ve konfigürasyon:

1. **Sunucu/Forge:** Yeni bir site oluştur (örn. `yeni.awapanel.com`); root = yeni dizin veya aynı repo farklı branch.
2. **Env:** O dizinde (veya Forge Environment) yeni `.env`: `APP_DOMAIN`, `APP_URL`, `FRONTEND_URL`, `CORS_ORIGINS`, `DB_*` (yeni DB veya aynı sunucuda yeni schema).
3. **Nginx:** Aynı snippet (API proxy + SPA fallback); sadece `server_name` ve gerekirse port farklı.
4. **Process:** Yeni site için ayrı daemon: `node backend/dist/main.js`, cwd yeni site kökü; PORT farklı olabilir (örn. 4101), Nginx’te `proxy_pass` o porta gider.
5. **Frontend build:** O site için build’te `VITE_API_URL` aynı domain’e ayarlanır (veya boş bırakılırsa origin + `/api` kullanılır).

**Kodda değişecek yer:** Yok; ancak CORS’ta yeni origin’in `.env`’de `CORS_ORIGINS` veya `FRONTEND_URL` ile gelmesi gerekir. Yani üçüncü subdomain’i sadece **yeni kurulum + env** ile ekliyorsunuz.

**İleride kodda subdomain bazlı tenant isterseniz:** O zaman örneğin bir middleware’de `req.hostname` / `req.subdomains` okuyup tenant’ı (company veya DB) seçip request’e eklemeniz; mevcut yapı buna hazır değil.

---

## 4. Hata Yönetimi ve Loglama

### 4.1 Global Exception Filter

- **Var:** `AllExceptionsFilter` (`common/filters/http-exception.filter.ts`).
- **Kayıt:** `main.ts` içinde `app.useGlobalFilters(new AllExceptionsFilter())`.
- **Davranış:** Tüm exception’ları yakalar (`@Catch()`); `HttpException` ise status + message, değilse 500; mesajları Türkçe’ye çeviren bir mapping ve DB hata mesajı çevirisi var; response’a `path`, `timestamp` eklenir.
- **Loglama:** Development’ta `console.error` ile exception + stack; production’da tek satır: `[METHOD url] status message`. Dosyaya veya yapısal (JSON) log’a yazılmıyor.

### 4.2 Eksikler

- **Yapısal loglama:** Winston/Pino vb. yok; sadece `console.error`. Production’da log agregasyonu (ör. PM2 log, Forge log) ham metin kalıyor.
- **Request ID / correlation ID:** Yok; bir isteğe ait log satırlarını eşleştirmek zor.
- **Hata sınıflandırması:** Tüm hatalar aynı filter’dan geçiyor; 4xx vs 5xx ayrımı var ama iş/domain hata kodları (örn. CONTRACT_NOT_FOUND) yok.
- **Bazı servislerde Logger:** Sadece `MailService` ve `SmsService` NestJS `Logger` kullanıyor; diğer modüllerde çoğunlukla `console.log` (örn. WarehousesService, CustomersService) — tutarsız ve production’da kapatılması zor.

---

## 5. Güvenlik ve Yetkilendirme

### 5.1 JWT

- **Modül:** `AuthModule`; `JwtModule.registerAsync` ile `ConfigService`’ten `JWT_SECRET`, `JWT_EXPIRES_IN` alınıyor.
- **Strateji:** `JwtStrategy` (Passport); token’dan `payload.sub` (user id) ile kullanıcı DB’den çekiliyor; aktif değilse 401. Request’e `user` objesi (id, email, role, company_id) ekleniyor.
- **Refresh:** `JWT_REFRESH_SECRET` ve `JWT_REFRESH_EXPIRES_IN` ile ayrı refresh token; frontend’de 401’de refresh deneyip tekrar istek atılıyor.

### 5.2 RBAC ve Guard’lar

- **Roller:** `UserRole` enum: `SUPER_ADMIN`, `COMPANY_OWNER`, `COMPANY_STAFF`, `DATA_ENTRY`, `ACCOUNTING`, `CUSTOMER`.
- **JwtAuthGuard:** Tüm route’lar varsayılan korumalı; `@Public()` olanlar (örn. login, `GET /companies/public/brand`) guard’ı atlıyor.
- **RolesGuard:** `@Roles(...)` ile metod bazlı rol; `Reflector` ile metadata’dan required roles alınır, `user.role` bunlardan biri olmalı.
- **Sıra:** Controller’da genelde `@UseGuards(JwtAuthGuard, RolesGuard)`; önce JWT, sonra rol kontrolü.

### 5.3 Company (Tenant) İzolasyonu

- **Merkezi middleware yok:** `TenantMiddleware` tanımlı ama **hiçbir modülde `apply()` edilmiyor**; etkisiz.
- **Gerçek izolasyon:** Her controller/service kendi içinde `companiesService.getCompanyIdForUser(user)` çağırıp, liste/güncelleme/silme işlemlerini `company_id` ile filtreliyor (örn. ContractsController’da `ensureContractAccess`, create’te customer/room company kontrolü). Super_admin tüm veriyi görebiliyor.

**Zayıf nokta:** Bir endpoint’te `companyId` kontrolü unutulursa veri sızıntısı riski; merkezi bir “tenant context” veya her repository çağrısında company scope zorunlu kılan bir katman yok.

### 5.4 Diğer

- **Rate limiting:** `ThrottlerModule` (100 istek/dakika) global `APP_GUARD` ile uygulanıyor.
- **CORS:** Origin listesi env’den (`CORS_ORIGINS` veya `FRONTEND_URL`); production’da boş bırakılamaz (main.ts’te hata fırlatılıyor).
- **Validation:** Global `ValidationPipe` (whitelist, transform); DTO’larda class-validator.

---

## 6. Taşınabilirlik ve Kurulum (Portability)

### 6.1 Zero Configuration İçin Eksikler

- **Node sürümü:** `package.json`’da `engines` yok; `.nvmrc` yok. Farklı sunucuda farklı Node ile çalışma/uyumsuzluk riski.
- **Veritabanı motoru:** Kod MySQL (TypeORM `mysql`); dokümantasyon ve deploy script MySQL varsayıyor. `docker-compose.yml` ise **PostgreSQL** ve `DATABASE_*` kullanıyor — proje ile uyumsuz; “sıfır ayar” ile docker-compose çalışmaz.
- **Ortam kontrolü:** Deploy/start öncesi “Node >= 18”, “MySQL erişilebilir”, “.env dolu mu?” gibi kontroller yok; sadece uygulama açılışında DB bağlanamazsa veya env eksikse hata alırsınız.
- **Migration:** Deploy script’te `npm run migration:run` var; migration’ların hangi sırayla çalıştığı ve geri alım (rollback) dokümante değil; ilk kurulumda migration hatası olursa adım adım kurtarma rehberi yok.

### 6.2 Bağımlılık Kontrolü

- **Backend/Frontend:** `npm ci` veya `npm run build` çalıştırılmadan önce Node/npm sürüm kontrolü yapılmıyor.
- **Öneri:** `engines` (package.json), `.nvmrc` ve isteğe bağlı bir `scripts/check-env.sh` (Node sürümü, .env varlığı, gerekirse DB bağlantı testi) eklenebilir.

---

## 7. Geliştirme Önerileri (Technical Debt & Clean Code)

### 7.1 TenantMiddleware Kullanılmıyor

`TenantMiddleware` tanımlı ama hiçbir yerde `apply()` edilmiyor. Ya kaldırılmalı ya da gerçekten tenant kontrolü (ör. company_id’yi request’e sabit set etmek) burada yapılmalı; aksi halde “yanıltıcı” kod kalır.

```ts
// backend/src/common/middleware/tenant.middleware.ts – şu an kimse apply etmiyor
```

**Öneri:** Middleware’i kullanacaksanız `AppModule` veya ilgili modüllerde `configure(Consumer)` ile ekleyin; kullanmayacaksanız dosyayı silin veya “TODO: central tenant enforcement” notu düşün.

### 7.2 Company Scope Tutarsızlığı

Bazı yerlerde `companyId` controller’da alınıp service’e veriliyor (contracts), bazı yerlerde service doğrudan repository’de company filtresi yapmıyor (örn. bazı listeler super_admin’e tümünü döndürüyor). İleride her “company-scoped” resource için tek bir kural olması iyi olur: örn. bir `@CompanyScoped()` decorator veya base service’te `getCompanyId()` zorunlu kullanımı.

### 7.3 Controller İçinde throw new Error('Unauthorized')

```ts
// companies.controller.ts (örnek)
if (user.role !== UserRole.SUPER_ADMIN && user.company_id !== id) {
  throw new Error('Unauthorized');  // ← HttpException değil; status 500 döner
}
```

**Öneri:** `throw new ForbiddenException('...')` kullanın; böylece status 403 ve mesaj tutarlı olur.

### 7.4 createContractDto: any

Contracts (ve muhtemelen başka yerler) için DTO tipi `any` kullanılıyor; validasyon ve tip güvenliği zayıflar.

**Öneri:** `CreateContractDto` sınıfı tanımlayıp class-validator ile kullanın; controller’da `@Body() createContractDto: CreateContractDto`.

### 7.5 console.log / console.error Dağınık Kullanım

WarehousesService, CustomersService, ContractsService içinde birçok `console.log` var (bildirim gönderme adımları). Production’da gürültü yapar ve seviye/kontekst kontrolü yok.

**Öneri:** NestJS `Logger` (veya tek bir logger servisi) kullanın; ortama göre log seviyesi kapatılabilsin.

### 7.6 Docker-Compose ile Uygulama Uyumsuzluğu

`docker-compose.yml` PostgreSQL ve `DATABASE_*` kullanıyor; uygulama MySQL ve `DB_*` bekliyor. “Sıfır ayar” ile çalışmaz.

**Öneri:** Ya docker-compose’u MySQL + `DB_*` env’e çevirin ya da “sadece local development için” diye not düşüp ayrı bir `docker-compose.mysql.yml` veya env örneği verin.

### 7.7 database.config.ts Entity Yolu

```ts
// database.config.ts
entities: [__dirname + '/../**/*.entity{.ts,.js}'],
```

Migration/CLI çalışırken `__dirname` config dosyasının bulunduğu dizin (`src/config`); buradan `../**/*.entity` entities’i kapsar ama `database.module.ts` `entities` array’ini `entities.ts`’ten import ediyor. İki farklı kaynak: biri glob, biri explicit list. Migration’da entity eksik/yanlış olma riski.

**Öneri:** Mümkünse migration config’te de aynı `entities` listesini (örn. bir shared config’ten) kullanın.

### 7.8 Env Yükleme Tekrarı

`main.ts` ve `ConfigModule` aynı `.env` dosyasını yüklüyor; `main.ts`’teki `override: true` ile process.env’i zorluyorsunuz. Tek bir “source of truth” (sadece ConfigModule ve Nest’in env’i okuması) daha sade olur; Forge enjeksiyonunu istemiyorsanız ConfigModule’e `ignoreEnvFile: false` ve doğru `envFilePath` vermek yeterli olabilir, main’deki dotenv kaldırılabilir (veya sadece “fallback” amaçlı bırakılıp dokümante edilir).

---

## 8. Özet Tablo

| Konu | Durum | Not |
|------|--------|-----|
| Mimari | Modüler, Controller–Service–Repository, Guard/Filter | Tutarlı |
| DB bağlantısı | Proje kökü .env, ConfigModule + dotenv | Taşınırken CWD’e dikkat |
| Multi-site | Sadece altyapı; kodda subdomain yok | Yeni site = yeni kurulum + env |
| Global exception filter | Var, Türkçe mesaj | Yapısal log yok |
| JWT + RBAC | Var, @Public, @Roles | TenantMiddleware kullanılmıyor |
| Taşınabilirlik | engines/.nvmrc yok; docker-compose MySQL değil | Zero config için eksikler var |
| Technical debt | any DTO, Error yerine ForbiddenException, console.log, TenantMiddleware | Küçük iyileştirmeler yeterli |

Bu döküm, projeyi başka bir sunucuya taşırken veya üçüncü subdomain’i eklerken referans olarak kullanılabilir; geliştirme önerileri ise teknik borcu azaltmak için uygulanabilir adımlardır.
