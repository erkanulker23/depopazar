# Path alias (@/)

Backend ve frontend’te `@/` alias’ı tanımlıdır; uzun relative import’lar (`../../..`) yerine kullanılabilir.

## Backend (NestJS)

- **tsconfig.json:** `"paths": { "@/*": ["src/*"] }`
- **Kullanım:** `import { X } from '@/common/filters/...'` (proje kökü `src/`)
- Build (Nest + webpack) path’leri çözümler; ek kurulum gerekmez.

## Frontend (Vite + React)

- **tsconfig.json:** `"paths": { "@/*": ["src/*"] }`
- **vite.config.ts:** `alias: { '@': path.resolve(__dirname, './src') }`
- **Kullanım:** `import { X } from '@/components/...'` veya `import { api } from '@/services/api/apiClient'`

## Örnek

```ts
// Eski (relative)
import { useAuthStore } from '../../../stores/authStore';

// Yeni (alias)
import { useAuthStore } from '@/stores/authStore';
```

Yeni kod yazarken `@/` kullanmanız önerilir; mevcut relative import’lar çalışmaya devam eder.
