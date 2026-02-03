import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { paths } from '../../routes/paths';
import { roomsApi } from '../../services/api/roomsApi';
import { contractsApi } from '../../services/api/contractsApi';
import {
  CubeIcon,
  ArrowLeftIcon,
  BuildingOfficeIcon,
  UserIcon,
  UsersIcon,
  PencilSquareIcon,
  TrashIcon,
  XMarkIcon,
} from '@heroicons/react/24/outline';
import { EditRoomModal } from '../../components/modals/EditRoomModal';
import { formatTurkishCurrency, formatTurkishNumber } from '../../utils/inputFormatters';

export function RoomDetailPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [room, setRoom] = useState<any>(null);
  const [contracts, setContracts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [editModalOpen, setEditModalOpen] = useState(false);
  const [deleteModalMode, setDeleteModalMode] = useState<'blocked' | 'confirm' | null>(null);
  const [deleteLoading, setDeleteLoading] = useState(false);
  const [deleteError, setDeleteError] = useState('');
  const [expandedCustomerId, setExpandedCustomerId] = useState<string | null>(null);

  const fetchRoom = async () => {
    if (!id) return;
    try {
      const roomData = await roomsApi.getById(id);
      setRoom(roomData);

      const contractsRes = await contractsApi.getAll({ limit: 100 });
      const roomContracts = (contractsRes.data || []).filter((c: any) => c.room_id === id);
      setContracts(roomContracts);
    } catch (error) {
      console.error('Error fetching room:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    setLoading(true);
    fetchRoom();
  }, [id]);

  if (loading) {
    return (
      <div className="modern-card p-8 text-center">
        <p className="text-gray-600 dark:text-gray-400">Yükleniyor...</p>
      </div>
    );
  }

  if (!room) {
    return (
      <div className="modern-card p-8 text-center">
        <p className="text-gray-600 dark:text-gray-400">Oda bulunamadı.</p>
      </div>
    );
  }

  const activeContract = contracts.find((c) => c.is_active);
  const contractMonths = activeContract
    ? Math.round(
        (new Date(activeContract.end_date).getTime() -
          new Date(activeContract.start_date).getTime()) /
          (1000 * 60 * 60 * 24 * 30),
      )
    : null;

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'occupied':
        return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
      case 'empty':
        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
      case 'reserved':
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
      case 'locked':
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusText = (status: string) => {
    const statusMap: Record<string, string> = {
      occupied: 'Dolu',
      empty: 'Boş',
      reserved: 'Rezerve',
      locked: 'Kilitli',
    };
    return statusMap[status] || status;
  };

  return (
    <div>
      <div className="mb-6">
        <button
          onClick={() => navigate(paths.odalar)}
          className="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 mb-4"
        >
          <ArrowLeftIcon className="h-4 w-4 mr-2" />
          Odalara Dön
        </button>
        <div className="flex items-center justify-between gap-4 flex-wrap">
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
            Oda: {room.room_number}
          </h1>
          <div className="flex items-center gap-2">
            <button
              onClick={() => setEditModalOpen(true)}
              className="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
              <PencilSquareIcon className="h-4 w-4 mr-2" />
              Düzenle
            </button>
            <button
              onClick={() => {
                setDeleteError('');
                setDeleteModalMode(activeContract ? 'blocked' : 'confirm');
              }}
              className="inline-flex items-center px-4 py-2 border border-red-300 dark:border-red-700 rounded-md shadow-sm text-sm font-medium text-red-700 dark:text-red-300 bg-white dark:bg-gray-800 hover:bg-red-50 dark:hover:bg-red-900/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
            >
              <TrashIcon className="h-4 w-4 mr-2" />
              Oda Sil
            </button>
          </div>
        </div>
      </div>

      {deleteModalMode && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          <div className="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div
              className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
              onClick={() => {
                if (!deleteLoading) {
                  setDeleteModalMode(null);
                  setDeleteError('');
                }
              }}
            />
            <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
              <div className="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                    {deleteModalMode === 'blocked' ? 'Oda silinemez' : 'Odayı Sil'}
                  </h3>
                  <button
                    onClick={() => {
                      if (!deleteLoading) {
                        setDeleteModalMode(null);
                        setDeleteError('');
                      }
                    }}
                    className="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                  >
                    <XMarkIcon className="h-6 w-6" />
                  </button>
                </div>
                {deleteError && (
                  <div className="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 p-3">
                    <p className="text-sm text-red-800 dark:text-red-200">{deleteError}</p>
                  </div>
                )}
                {deleteModalMode === 'blocked' ? (
                  <>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                      <strong className="text-gray-900 dark:text-white">Bu odada müşteri var.</strong>{' '}
                      Odayı silebilmek için önce sözleşmeyi sonlandırmanız gerekiyor.
                    </p>
                    {(() => {
                      const activeContractsToTerminate = contracts.filter((c) => c.is_active);
                      return activeContractsToTerminate.length > 0 ? (
                        <div className="mb-4 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                          <p className="text-xs font-semibold text-amber-800 dark:text-amber-200 uppercase tracking-wide mb-2">
                            Sonlandırılması gereken sözleşmeler
                          </p>
                          <ul className="space-y-1 text-sm text-amber-900 dark:text-amber-100">
                            {activeContractsToTerminate.map((c) => (
                              <li key={c.id} className="flex items-center justify-between">
                                <span>
                                  {c.contract_number} – {c.customer?.first_name} {c.customer?.last_name}
                                </span>
                                <button
                                  type="button"
                                  onClick={() => {
                                    navigate(paths.girisDetay(c.id));
                                    setDeleteModalMode(null);
                                  }}
                                  className="text-primary-600 hover:text-primary-700 dark:text-primary-400 text-xs font-medium"
                                >
                                  Sözleşmeye git →
                                </button>
                              </li>
                            ))}
                          </ul>
                        </div>
                      ) : null;
                    })()}
                    <div className="flex justify-end">
                      <button
                        type="button"
                        onClick={() => {
                          setDeleteModalMode(null);
                          setDeleteError('');
                        }}
                        className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                      >
                        Tamam
                      </button>
                    </div>
                  </>
                ) : (
                  <>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                      <strong className="text-gray-900 dark:text-white">{room.room_number}</strong>{' '}
                      odasını silmek istediğinize emin misiniz?
                    </p>
                    <div className="flex justify-end space-x-3">
                      <button
                        type="button"
                        onClick={() => {
                          if (!deleteLoading) {
                            setDeleteModalMode(null);
                            setDeleteError('');
                          }
                        }}
                        disabled={deleteLoading}
                        className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50"
                      >
                        İptal
                      </button>
                      <button
                        type="button"
                        onClick={async () => {
                          if (!id) return;
                          setDeleteError('');
                          setDeleteLoading(true);
                          try {
                            await roomsApi.remove(id);
                            setDeleteModalMode(null);
                            navigate(paths.odalar);
                          } catch (err: any) {
                            setDeleteError(
                              err.response?.data?.message || 'Oda silinirken bir hata oluştu',
                            );
                          } finally {
                            setDeleteLoading(false);
                          }
                        }}
                        disabled={deleteLoading}
                        className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50"
                      >
                        {deleteLoading ? 'Siliniyor...' : 'Sil'}
                      </button>
                    </div>
                  </>
                )}
              </div>
            </div>
          </div>
        </div>
      )}

      <EditRoomModal
        isOpen={editModalOpen}
        onClose={() => setEditModalOpen(false)}
        onSuccess={fetchRoom}
        room={room}
      />

      {/* Oda Bilgileri - Modern Card Design */}
      <div className="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-8 mb-6">
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
            <div className="p-3 bg-primary-100 dark:bg-primary-900/30 rounded-xl mr-3">
              <CubeIcon className="h-6 w-6 text-primary-600 dark:text-primary-400" />
            </div>
            Oda Bilgileri
          </h2>
          <span
            className={`px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-full ${getStatusColor(
              room.status,
            )}`}
          >
            {getStatusText(room.status)}
          </span>
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div className="bg-white dark:bg-gray-800/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <label className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2 block">Depo</label>
            <p className="text-base font-semibold text-gray-900 dark:text-white flex items-center">
              <BuildingOfficeIcon className="h-5 w-5 mr-2 text-primary-500" />
              {room.warehouse?.name || '-'}
            </p>
          </div>
          
          <div className="bg-white dark:bg-gray-800/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <label className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2 block">Alan</label>
            <p className="text-base font-semibold text-gray-900 dark:text-white">
              {formatTurkishNumber(Number(room.area_m2), 2)} m²
            </p>
          </div>
          
          <div className="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 rounded-xl p-4 border-2 border-primary-200 dark:border-primary-800">
            <label className="text-xs font-semibold text-primary-600 dark:text-primary-400 uppercase tracking-wide mb-2 block">Aylık Fiyat</label>
            <p className="text-xl font-bold text-primary-700 dark:text-primary-300">
              {formatTurkishCurrency(Number(room.monthly_price))}
            </p>
          </div>
          
          {room.floor && (
            <div className="bg-white dark:bg-gray-800/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
              <label className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2 block">Kat</label>
              <p className="text-base font-semibold text-gray-900 dark:text-white">{room.floor}</p>
            </div>
          )}
          
          {room.block && (
            <div className="bg-white dark:bg-gray-800/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
              <label className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2 block">Blok</label>
              <p className="text-base font-semibold text-gray-900 dark:text-white">{room.block}</p>
            </div>
          )}
          
          {room.corridor && (
            <div className="bg-white dark:bg-gray-800/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
              <label className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2 block">Koridor</label>
              <p className="text-base font-semibold text-gray-900 dark:text-white">{room.corridor}</p>
            </div>
          )}
          
          {contractMonths && (
            <div className="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-4 border-2 border-blue-200 dark:border-blue-800">
              <label className="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wide mb-2 block">
                Sözleşme Süresi
              </label>
              <p className="text-xl font-bold text-blue-700 dark:text-blue-300">
                {contractMonths} Aylık
              </p>
            </div>
          )}
        </div>
        
        {room.description && (
          <div className="mt-6 bg-white dark:bg-gray-800/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <label className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2 block">Açıklama</label>
            <p className="text-sm text-gray-900 dark:text-white leading-relaxed">{room.description}</p>
          </div>
        )}
      </div>

      {/* Müşteriler - Modern Card Design */}
      {contracts.length > 0 && (
        <div className="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-8 mb-6">
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
              <div className="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl mr-3">
                <UsersIcon className="h-6 w-6 text-blue-600 dark:text-blue-400" />
              </div>
              Odayı Kullanan Müşteriler
            </h2>
            <span className="px-4 py-2 bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 rounded-full text-sm font-semibold">
              {contracts.filter((c) => c.is_active).length} Aktif
            </span>
          </div>
          {contracts.length > 0 ? (
            <div className="space-y-3">
              {contracts.map((contract) => {
                const isExpanded = expandedCustomerId === contract.id;
                const months = Math.round(
                  (new Date(contract.end_date).getTime() -
                    new Date(contract.start_date).getTime()) /
                    (1000 * 60 * 60 * 24 * 30),
                );
                return (
                  <div
                    key={contract.id}
                    className="bg-gradient-to-br from-primary-50 via-white to-primary-50 dark:from-primary-900/20 dark:via-gray-800 dark:to-primary-900/20 rounded-xl border-2 border-primary-200 dark:border-primary-800 shadow-lg overflow-hidden transition-all"
                  >
                    <button
                      onClick={() => setExpandedCustomerId(isExpanded ? null : contract.id)}
                      className="w-full p-6 flex items-center justify-between hover:bg-primary-100/50 dark:hover:bg-primary-900/30 transition-colors"
                    >
                      <div className="flex items-center gap-4 flex-1">
                        <div className="flex items-center gap-3">
                          {contract.is_active ? (
                            <span className="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
                          ) : (
                            <span className="w-3 h-3 bg-gray-400 rounded-full"></span>
                          )}
                          <h3 className="text-lg font-bold text-gray-900 dark:text-white">
                            {contract.contract_number}
                          </h3>
                        </div>
                        <div className="flex items-center gap-2">
                          <UserIcon className="h-5 w-5 text-primary-500" />
                          <span className="text-base font-semibold text-gray-900 dark:text-white">
                            {contract.customer?.first_name} {contract.customer?.last_name}
                          </span>
                        </div>
                        <span className="px-3 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded-full text-xs font-semibold">
                          {months} Aylık Sözleşme
                        </span>
                        <span
                          className={`px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${
                            contract.is_active
                              ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                              : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
                          }`}
                        >
                          {contract.is_active ? 'Aktif' : 'Pasif'}
                        </span>
                      </div>
                      <div className="ml-4">
                        <svg
                          className={`w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform ${
                            isExpanded ? 'transform rotate-180' : ''
                          }`}
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                        </svg>
                      </div>
                    </button>
                    {isExpanded && (
                      <div className="px-6 pb-6 pt-0 border-t border-primary-200 dark:border-primary-800">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                          <div className="bg-white dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <p className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Müşteri</p>
                            <p className="text-base font-semibold text-gray-900 dark:text-white flex items-center">
                              <UserIcon className="h-5 w-5 mr-2 text-primary-500" />
                              {contract.customer?.first_name} {contract.customer?.last_name}
                            </p>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-2">
                              {contract.customer?.email}
                            </p>
                            {contract.customer?.phone && (
                              <p className="text-sm text-gray-500 dark:text-gray-400">
                                {contract.customer.phone}
                              </p>
                            )}
                          </div>
                          <div className="bg-white dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <p className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Aylık Fiyat</p>
                            <p className="text-xl font-bold text-primary-600 dark:text-primary-400">
                              {formatTurkishCurrency(Number(contract.monthly_price))}
                            </p>
                          </div>
                          <div className="bg-white dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <p className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Başlangıç</p>
                            <p className="text-base font-semibold text-gray-900 dark:text-white">
                              {new Date(contract.start_date).toLocaleDateString('tr-TR', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                              })}
                            </p>
                          </div>
                          <div className="bg-white dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                            <p className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Bitiş</p>
                            <p className="text-base font-semibold text-gray-900 dark:text-white">
                              {new Date(contract.end_date).toLocaleDateString('tr-TR', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                              })}
                            </p>
                          </div>
                        </div>
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          ) : (
            <p className="text-sm text-gray-500 dark:text-gray-400">
              Bu odada müşteri bulunmamaktadır.
            </p>
          )}
        </div>
      )}

      {/* Sözleşme Geçmişi - Modern Table Design */}
      {contracts.length > 0 && (
        <div className="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-8">
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
              Sözleşme Geçmişi
            </h2>
            <span className="px-4 py-2 bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 rounded-full text-sm font-semibold">
              {contracts.length} Sözleşme
            </span>
          </div>
          <div className="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead className="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                <tr>
                  <th className="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    Sözleşme No
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    Müşteri
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    Süre
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    Başlangıç
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    Bitiş
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    Durum
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                {contracts.map((contract) => {
                  const months = Math.round(
                    (new Date(contract.end_date).getTime() -
                      new Date(contract.start_date).getTime()) /
                      (1000 * 60 * 60 * 24 * 30),
                  );
                  return (
                    <tr key={contract.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                      <td className="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                        {contract.contract_number}
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                        {contract.customer?.first_name} {contract.customer?.last_name}
                      </td>
                      <td className="px-6 py-4">
                        <span className="px-3 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded-full text-xs font-semibold">
                          {months} Ay
                        </span>
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                        {new Date(contract.start_date).toLocaleDateString('tr-TR', {
                          year: 'numeric',
                          month: 'short',
                          day: 'numeric'
                        })}
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                        {new Date(contract.end_date).toLocaleDateString('tr-TR', {
                          year: 'numeric',
                          month: 'short',
                          day: 'numeric'
                        })}
                      </td>
                      <td className="px-6 py-4">
                        <span
                          className={`px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${
                            contract.is_active
                              ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                              : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
                          }`}
                        >
                          {contract.is_active ? 'Aktif' : 'Pasif'}
                        </span>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}
