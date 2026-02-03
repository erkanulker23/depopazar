import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { paths } from '../../routes/paths';
import { proposalsApi, Proposal } from '../../services/api/proposalsApi';
import { companiesApi } from '../../services/api/companiesApi';
import { generateProposalPDF } from '../../utils/pdfUtils';
import { getErrorMessage } from '../../utils/errorMessage';
import { PlusIcon, TrashIcon, ArrowDownTrayIcon, EnvelopeIcon, PrinterIcon, PencilIcon } from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';

export function ProposalsPage() {
  const [proposals, setProposals] = useState<Proposal[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const navigate = useNavigate();

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await proposalsApi.getAll();
      setProposals(data);
    } catch (err: unknown) {
      const message = getErrorMessage(err);
      setError(message);
      toast.error(message);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id: string) => {
    if (!confirm('Teklifi silmek istediğinize emin misiniz?')) return;
    try {
      await proposalsApi.delete(id);
      toast.success('Teklif silindi');
      loadData();
    } catch (err: unknown) {
      toast.error(getErrorMessage(err));
    }
  };

  const handlePdfDownload = async (proposal: Proposal) => {
    try {
      const [full, company] = await Promise.all([
        proposalsApi.getById(proposal.id),
        companiesApi.getCurrent().catch(() => null),
      ]);
      await generateProposalPDF(full, company ?? undefined);
      toast.success('PDF indirildi');
    } catch (e: unknown) {
      toast.error(getErrorMessage(e));
    }
  };

  const handlePrint = async (proposal: Proposal) => {
    try {
      const full = await proposalsApi.getById(proposal.id);
      const win = window.open('', '_blank');
      if (!win) {
        toast.error('Yazdırma penceresi açılamadı');
        return;
      }
      win.document.write(`
        <!DOCTYPE html>
        <html><head><title>Teklif - ${full.title}</title>
        <style>body{font-family:sans-serif;padding:24px;max-width:800px;margin:0 auto}
        h1{font-size:1.5rem} table{width:100%;border-collapse:collapse}
        th,td{border:1px solid #ddd;padding:8px;text-align:left}
        .total{font-weight:bold;margin-top:16px}
        .terms{white-space:pre-wrap;margin-top:16px;padding:12px;background:#f5f5f5;border-radius:8px}</style>
        </head><body>
        <h1>${full.title}</h1>
        <p>Tarih: ${new Date(full.created_at).toLocaleDateString('tr-TR')}</p>
        ${full.customer ? `<p>Müşteri: ${full.customer.first_name} ${full.customer.last_name}</p>` : ''}
        <table><thead><tr><th>Hizmet/Ürün</th><th>Miktar</th><th>Birim Fiyat</th><th>Toplam</th></tr></thead>
        <tbody>${(full.items || []).map((i: any) => `<tr><td>${i.name}</td><td>${i.quantity}</td><td>${i.unit_price} ₺</td><td>${i.total_price} ₺</td></tr>`).join('')}</tbody>
        </table>
        <p class="total">Toplam: ${full.currency} ${Number(full.total_amount).toLocaleString('tr-TR')} ₺</p>
        ${full.transport_terms ? `<div class="terms"><strong>Taşıma Şartları:</strong><br>${full.transport_terms.replace(/\n/g, '<br>')}</div>` : ''}
        </body></html>
      `);
      win.document.close();
      win.focus();
      setTimeout(() => { win.print(); win.close(); }, 250);
      toast.success('Yazdırma penceresi açıldı');
    } catch (e: unknown) {
      toast.error(getErrorMessage(e));
    }
  };

  const handleEmail = (proposal: Proposal) => {
    const email = proposal.customer?.email;
    if (!email) {
      toast.error('Müşteri e-posta adresi bulunamadı');
      return;
    }
    const subject = encodeURIComponent(`Teklif: ${proposal.title}`);
    const body = encodeURIComponent(
      `Sayın ${proposal.customer?.first_name} ${proposal.customer?.last_name},\n\n` +
      `${proposal.title} teklifimiz ekte sunulmuştur.\n\nToplam: ${proposal.currency} ${Number(proposal.total_amount).toLocaleString('tr-TR')} ₺`
    );
    window.location.href = `mailto:${email}?subject=${subject}&body=${body}`;
    toast.success('E-posta uygulaması açıldı');
  };

  if (loading) return <div className="p-4">Yükleniyor...</div>;

  if (error) {
    return (
      <div className="p-6 max-w-md mx-auto">
        <div className="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
          <p className="font-medium text-amber-800 dark:text-amber-200">Teklifler yüklenemedi</p>
          <p className="text-sm text-amber-700 dark:text-amber-300 mt-2">{error}</p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Teklifler</h1>
          <p className="text-sm text-gray-500 dark:text-gray-400">Müşterilere verilen teklifleri yönetin</p>
        </div>
        <button
          onClick={() => navigate(paths.teklifYeni)}
          className="flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors"
        >
          <PlusIcon className="w-5 h-5 mr-2" />
          Teklif Oluştur
        </button>
      </div>

      <div className="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        {proposals.length === 0 ? (
          <div className="p-8 text-center text-gray-500 dark:text-gray-400">
            Henüz teklif oluşturulmamış.
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead className="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tarih</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Başlık</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Müşteri</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tutar</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Durum</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">İşlemler</th>
                </tr>
              </thead>
              <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                {proposals.map((proposal) => (
                  <tr key={proposal.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                      {new Date(proposal.created_at).toLocaleDateString('tr-TR')}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                      {proposal.title}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                      {proposal.customer ? `${proposal.customer.first_name} ${proposal.customer.last_name}` : '-'}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                      {new Intl.NumberFormat('tr-TR', { style: 'currency', currency: proposal.currency }).format(proposal.total_amount)}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                        ${proposal.status === 'draft' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : 
                          proposal.status === 'sent' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300' :
                          proposal.status === 'accepted' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' :
                          proposal.status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300' :
                          'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300'
                        }`}>
                        {proposal.status === 'draft' ? 'Taslak' : 
                         proposal.status === 'sent' ? 'Gönderildi' :
                         proposal.status === 'accepted' ? 'Onaylandı' :
                         proposal.status === 'rejected' ? 'Reddedildi' :
                         proposal.status === 'expired' ? 'Süresi Doldu' : proposal.status}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div className="flex items-center justify-end space-x-2">
                        <button
                          onClick={() => navigate(paths.teklifDuzenle(proposal.id))}
                          className="text-gray-400 hover:text-primary-600 dark:hover:text-primary-400"
                          title="Düzenle"
                        >
                          <PencilIcon className="h-5 w-5" />
                        </button>
                        <button 
                          onClick={() => handlePdfDownload(proposal)}
                          className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" 
                          title="PDF İndir"
                        >
                          <ArrowDownTrayIcon className="h-5 w-5" />
                        </button>
                        <button 
                          onClick={() => handleEmail(proposal)}
                          className="text-blue-400 hover:text-blue-600 dark:hover:text-blue-300" 
                          title="E-posta Gönder"
                        >
                          <EnvelopeIcon className="h-5 w-5" />
                        </button>
                        <button 
                          onClick={() => handlePrint(proposal)}
                          className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" 
                          title="Yazdır"
                        >
                          <PrinterIcon className="h-5 w-5" />
                        </button>
                        <button 
                          onClick={() => handleDelete(proposal.id)}
                          className="text-red-400 hover:text-red-500" 
                          title="Sil"
                        >
                          <TrashIcon className="h-5 w-5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
