import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { proposalsApi, Proposal } from '../../services/api/proposalsApi';
import { PlusIcon, EyeIcon, PencilIcon, TrashIcon } from '@heroicons/react/24/outline';
import toast from 'react-hot-toast';

export function ProposalsPage() {
  const [proposals, setProposals] = useState<Proposal[]>([]);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      setLoading(true);
      const data = await proposalsApi.getAll();
      setProposals(data);
    } catch (error) {
      toast.error('Teklifler yüklenirken hata oluştu');
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
    } catch (error) {
      toast.error('Teklif silinemedi');
    }
  };

  if (loading) return <div className="p-4">Yükleniyor...</div>;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Teklifler</h1>
          <p className="text-sm text-gray-500 dark:text-gray-400">Müşterilere verilen teklifleri yönetin</p>
        </div>
        <button
          onClick={() => navigate('/proposals/new')}
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
                        {/* <button className="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300" title="Görüntüle">
                          <EyeIcon className="h-5 w-5" />
                        </button> */}
                        {/* <button className="text-blue-400 hover:text-blue-500" title="Düzenle">
                          <PencilIcon className="h-5 w-5" />
                        </button> */}
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
