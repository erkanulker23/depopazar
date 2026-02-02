import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { roomsApi } from '../../services/api/roomsApi';
import { CubeIcon, PlusIcon, MagnifyingGlassIcon, FunnelIcon, TrashIcon, XMarkIcon } from '@heroicons/react/24/outline';
import { AddRoomModal } from '../../components/modals/AddRoomModal';
import { formatTurkishCurrency, formatTurkishNumber } from '../../utils/inputFormatters';

export function RoomsPage() {
  const navigate = useNavigate();
  const [rooms, setRooms] = useState<any[]>([]);
  const [filteredRooms, setFilteredRooms] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [addModalOpen, setAddModalOpen] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [warehouseFilter, setWarehouseFilter] = useState<string>('all');
  const [selectedRooms, setSelectedRooms] = useState<Set<string>>(new Set());
  const [deleteTarget, setDeleteTarget] = useState<any>(null);
  const [deleteModalMode, setDeleteModalMode] = useState<'blocked' | 'confirm' | null>(null);
  const [deleteLoading, setDeleteLoading] = useState(false);
  const [deleteError, setDeleteError] = useState('');

  const fetchRooms = async () => {
    try {
      const data = await roomsApi.getAll();
      setRooms(data);
      setFilteredRooms(data);
    } catch (error) {
      console.error('Error fetching rooms:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    setLoading(true);
    fetchRooms();
  }, []);

  useEffect(() => {
    let filtered = rooms;

    // Search filter
    if (searchTerm.trim() !== '') {
      const searchLower = searchTerm.toLowerCase();
      filtered = filtered.filter(
        (room) =>
          room.room_number?.toLowerCase().includes(searchLower) ||
          room.warehouse?.name?.toLowerCase().includes(searchLower) ||
          room.area_m2?.toString().includes(searchTerm) ||
          room.monthly_price?.toString().includes(searchTerm),
      );
    }

    // Status filter
    if (statusFilter !== 'all') {
      filtered = filtered.filter((room) => room.status === statusFilter);
    }

    // Warehouse filter
    if (warehouseFilter !== 'all') {
      filtered = filtered.filter((room) => room.warehouse_id === warehouseFilter);
    }

    setFilteredRooms(filtered);
  }, [searchTerm, statusFilter, warehouseFilter, rooms]);

  // Get unique warehouses for filter dropdown
  const uniqueWarehouses = Array.from(
    new Map(rooms.map((room) => [room.warehouse_id, room.warehouse])).values(),
  ).filter(Boolean);

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
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-3xl font-bold gradient-text mb-2">Odalar</h1>
          <p className="text-gray-600 dark:text-gray-400">Oda yönetimi ve durum takibi</p>
        </div>
        <button
          onClick={() => setAddModalOpen(true)}
          className="btn-primary inline-flex items-center px-6 py-3"
        >
          <PlusIcon className="h-5 w-5 mr-2" />
          Oda Ekle
        </button>
      </div>

      <AddRoomModal
        isOpen={addModalOpen}
        onClose={() => setAddModalOpen(false)}
        onSuccess={fetchRooms}
      />

      {/* Delete Modal */}
      {deleteTarget && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          <div className="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div
              className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
              onClick={() => {
                if (!deleteLoading) {
                  setDeleteTarget(null);
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
                        setDeleteTarget(null);
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
                {deleteModalMode === 'blocked' && deleteTarget && deleteTarget !== 'bulk' ? (
                  <>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                      <strong className="text-gray-900 dark:text-white">Bu odada müşteri var.</strong>{' '}
                      Odayı silebilmek için önce sözleşmeyi sonlandırmanız gerekiyor.
                    </p>
                    {(() => {
                      const activeContracts = (deleteTarget.contracts ?? []).filter((c: any) => c.is_active);
                      return activeContracts.length > 0 ? (
                        <div className="mb-4 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                          <p className="text-xs font-semibold text-amber-800 dark:text-amber-200 uppercase tracking-wide mb-2">
                            Sonlandırılması gereken sözleşmeler
                          </p>
                          <ul className="space-y-1 text-sm text-amber-900 dark:text-amber-100">
                            {activeContracts.map((c: any) => (
                              <li key={c.id} className="flex items-center justify-between">
                                <span>
                                  {c.contract_number} – {c.customer?.first_name} {c.customer?.last_name}
                                </span>
                                <button
                                  type="button"
                                  onClick={() => {
                                    navigate(`/contracts/${c.id}`);
                                    setDeleteTarget(null);
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
                        onClick={() => { setDeleteTarget(null); setDeleteModalMode(null); setDeleteError(''); }}
                        className="px-4 py-2 text-white bg-primary-600 hover:bg-primary-700 rounded-md text-sm font-medium"
                      >
                        Tamam
                      </button>
                    </div>
                  </>
                ) : (
                  <>
                <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                  {deleteTarget === 'bulk' ? (
                    <>
                      <strong>{selectedRooms.size} oda</strong> silmek istediğinize emin misiniz?
                      Bu işlem geri alınamaz.
                    </>
                  ) : (
                    <>
                      <strong className="text-gray-900 dark:text-white">
                        {deleteTarget?.room_number}
                      </strong>{' '}
                      odasını silmek istediğinize emin misiniz? Bu işlem geri alınamaz.
                    </>
                  )}
                </p>
                <div className="flex justify-end space-x-3">
                  <button
                    type="button"
                    onClick={() => {
                      if (!deleteLoading) {
                        setDeleteTarget(null);
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
                      setDeleteError('');
                      setDeleteLoading(true);
                      try {
                        if (deleteTarget === 'bulk') {
                          await roomsApi.bulkDelete(Array.from(selectedRooms));
                          setSelectedRooms(new Set());
                        } else {
                          await roomsApi.remove(deleteTarget.id);
                        }
                        setDeleteTarget(null);
                        setDeleteModalMode(null);
                        fetchRooms();
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

      {/* Toplu İşlemler Toolbar */}
      {selectedRooms.size > 0 && (
        <div className="mb-4 p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg flex items-center justify-between">
          <span className="text-sm font-medium text-primary-900 dark:text-primary-200">
            {selectedRooms.size} oda seçildi
          </span>
          <div className="flex gap-2">
            <button
              onClick={() => setSelectedRooms(new Set())}
              className="px-3 py-1 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
            >
              Seçimi Temizle
            </button>
            <button
              onClick={() => {
                setDeleteError('');
                setDeleteTarget('bulk');
                setDeleteModalMode('confirm');
              }}
              className="px-3 py-1 text-sm text-white bg-red-600 hover:bg-red-700 rounded inline-flex items-center"
            >
              <TrashIcon className="h-4 w-4 mr-1" />
              Seçilenleri Sil
            </button>
          </div>
        </div>
      )}

      {/* Filtreleme ve Arama */}
      <div className="mb-4 space-y-4">
        <div className="relative">
          <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
          </div>
          <input
            type="text"
            placeholder="Oda ara (oda no, depo, alan, fiyat)..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
          />
        </div>
        <div className="flex gap-4 flex-wrap">
          <div className="flex items-center gap-2">
            <FunnelIcon className="h-5 w-5 text-gray-400" />
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="block px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
            >
              <option value="all">Tüm Durumlar</option>
              <option value="occupied">Dolu</option>
              <option value="empty">Boş</option>
              <option value="reserved">Rezerve</option>
              <option value="locked">Kilitli</option>
            </select>
          </div>
          <div className="flex items-center gap-2">
            <select
              value={warehouseFilter}
              onChange={(e) => setWarehouseFilter(e.target.value)}
              className="block px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
            >
              <option value="all">Tüm Depolar</option>
              {uniqueWarehouses.map((warehouse: any) => (
                <option key={warehouse.id} value={warehouse.id}>
                  {warehouse.name}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {loading ? (
        <div className="modern-card p-8 text-center">
          <p className="text-gray-600 dark:text-gray-400">Yükleniyor...</p>
        </div>
      ) : filteredRooms.length === 0 ? (
        <div className="modern-card p-8 text-center">
          <p className="text-gray-600 dark:text-gray-400">
            {(() => {
              if (rooms.length === 0) {
                return 'Henüz oda bulunmamaktadır.';
              }
              return 'Arama kriterlerinize uygun oda bulunamadı.';
            })()}
          </p>
        </div>
      ) : (
        <div className="modern-card overflow-hidden">
          <div className="overflow-x-auto">
            <table className="table-modern">
              <thead>
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-12">
                    <input
                      type="checkbox"
                      checked={selectedRooms.size === filteredRooms.length && filteredRooms.length > 0}
                      onChange={(e) => {
                        if (e.target.checked) {
                          setSelectedRooms(new Set(filteredRooms.map((r) => r.id)));
                        } else {
                          setSelectedRooms(new Set());
                        }
                      }}
                      className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                      onClick={(e) => e.stopPropagation()}
                    />
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Oda No
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Depo
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Alan (m²)
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Aylık Fiyat
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Durum
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    İşlemler
                  </th>
                </tr>
              </thead>
              <tbody>
                {filteredRooms.map((room) => (
                  <tr
                    key={room.id}
                    className="hover:bg-gray-50 dark:hover:bg-gray-700"
                  >
                    <td className="px-6 py-4 whitespace-nowrap" onClick={(e) => e.stopPropagation()}>
                      <input
                        type="checkbox"
                        checked={selectedRooms.has(room.id)}
                        onChange={(e) => {
                          const newSelected = new Set(selectedRooms);
                          if (e.target.checked) {
                            newSelected.add(room.id);
                          } else {
                            newSelected.delete(room.id);
                          }
                          setSelectedRooms(newSelected);
                        }}
                        className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        onClick={(e) => e.stopPropagation()}
                      />
                    </td>
                    <td
                      className="px-6 py-4 whitespace-nowrap cursor-pointer"
                      onClick={() => navigate(`/rooms/${room.id}`)}
                    >
                      <div className="flex items-center">
                        <CubeIcon className="h-5 w-5 text-primary-500 mr-2" />
                        <span className="text-sm font-medium text-gray-900 dark:text-white">
                          {room.room_number}
                        </span>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                      {room.warehouse?.name || '-'}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                      {formatTurkishNumber(Number(room.area_m2), 2)} m²
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                      {formatTurkishCurrency(Number(room.monthly_price))}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span
                        className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusColor(
                          room.status,
                        )}`}
                      >
                        {getStatusText(room.status)}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap" onClick={(e) => e.stopPropagation()}>
                      <button
                        onClick={() => {
                          setDeleteError('');
                          const hasActiveContract = (room.contracts ?? []).some((c: any) => c.is_active);
                          setDeleteTarget(room);
                          setDeleteModalMode(hasActiveContract ? 'blocked' : 'confirm');
                        }}
                        className="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                        title="Sil"
                      >
                        <TrashIcon className="h-5 w-5" />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}
