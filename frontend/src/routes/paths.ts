/**
 * Türkçe URL slug'ları – tek kaynak
 */
export const paths = {
  giris: '/giris',

  // Admin panel
  genelBakis: '/genel-bakis',
  girisler: '/girisler',
  girisYeni: '/girisler/yeni',
  girisDetay: (id: string) => `/girisler/${id}`,
  odemeler: '/odemeler',
  odemeAl: '/odemeler?collect=true',
  odemelerBasarili: '/odemeler/basarili',
  odemelerHata: '/odemeler/hata',
  nakliyeIsler: '/nakliye-isler',
  hizmetler: '/hizmetler',
  teklifler: '/teklifler',
  teklifYeni: '/teklifler/yeni',
  teklifDuzenle: (id: string) => `/teklifler/${id}/duzenle`,
  kullanicilar: '/kullanicilar',
  kullaniciDetay: (id: string) => `/kullanicilar/${id}`,
  yetkiler: '/yetkiler',
  depolar: '/depolar',
  odalar: '/odalar',
  odaDetay: (id: string) => `/odalar/${id}`,
  musteriler: '/musteriler',
  musteriDetay: (id: string) => `/musteriler/${id}`,
  raporlar: '/raporlar',
  raporlarBankaHesaplari: '/raporlar/banka-hesaplari',
  ayarlar: '/ayarlar',

  // Müşteri paneli
  musteri: {
    genelBakis: '/musteri/genel-bakis',
    sozlesmeler: '/musteri/sozlesmeler',
    sozlesmeDetay: (id: string) => `/musteri/sozlesmeler/${id}`,
    odemeler: '/musteri/odemeler',
  },
} as const;
