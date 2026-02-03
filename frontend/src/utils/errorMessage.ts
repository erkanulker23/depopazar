/**
 * API/axios hatalarından kullanıcıya gösterilecek net mesajı çıkarır.
 * "Veriler yüklenirken hata oluştu" gibi genel ifadeler yerine hatanın kaynağını gösterir.
 */
export function getErrorMessage(err: unknown): string {
  if (err == null) return 'Bilinmeyen hata';
  if (typeof err === 'string') return err;
  if (typeof err !== 'object') return 'Bilinmeyen hata';

  const ax = err as { response?: { data?: { message?: string | string[] }; status?: number }; message?: string };
  const msg = ax.response?.data?.message;
  const status = ax.response?.status;

  if (msg != null) {
    const text = Array.isArray(msg) ? msg.join(' ') : msg;
    if (text.trim()) return text;
  }

  if (status === 400) return 'İstek geçersiz (400). Gönderilen veriler hatalı veya eksik olabilir.';
  if (status === 401) return 'Oturum süresi dolmuş veya yetkisiz erişim (401). Lütfen tekrar giriş yapın.';
  if (status === 403) return 'Bu işlem için yetkiniz yok (403).';
  if (status === 404) return 'İstenen kaynak bulunamadı (404).';
  if (status === 422) return 'Doğrulama hatası (422). Lütfen girdiğiniz bilgileri kontrol edin.';
  if (status && status >= 500) return `Sunucu hatası (${status}). Lütfen kısa süre sonra tekrar deneyin.`;

  if (ax.message) {
    if (ax.message === 'Network Error') return 'Ağ hatası: Sunucuya ulaşılamıyor. İnternet bağlantınızı ve API adresini kontrol edin.';
    return ax.message;
  }

  return 'Bilinmeyen hata. Tarayıcı konsolunda (F12) detay görebilirsiniz.';
}
