import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { paths } from '../../routes/paths';
import { warehousesApi } from '../../services/api/warehousesApi';
import {
  BuildingOfficeIcon,
  PlusIcon,
  PencilIcon,
  TrashIcon,
  CubeIcon,
  UsersIcon,
  ChevronDownIcon,
  ChevronUpIcon,
  XMarkIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
} from '@heroicons/react/24/outline';
import { AddWarehouseModal } from '../../components/modals/AddWarehouseModal';
import { EditWarehouseModal } from '../../components/modals/EditWarehouseModal';
import { formatTurkishCurrency, formatTurkishNumber } from '../../utils/inputFormatters';

export function WarehousesPage() {
  const navigate = useNavigate();
  const [warehouses, setWarehouses] = useState<any[]>([]);
  const [filteredWarehouses, setFilteredWarehouses] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [selectedWarehouse, setSelectedWarehouse] = useState<any>(null);
  const [deleteTarget, setDeleteTarget] = useState<any>(null);
  const [deleteLoading, setDeleteLoading] = useState(false);
  const [deleteError, setDeleteError] = useState('');
  const [expandedWarehouses, setExpandedWarehouses] = useState<Set<string>>(new Set());
  const [searchTerm, setSearchTerm] = useState('');
  const [cityFilter, setCityFilter] = useState<string>('all');

  const fetchWarehouses = async () => {
    try {
      const data = await warehousesApi.getAll();
      setWarehouses(data);
      setFilteredWarehouses(data);
    } catch (error) {
      console.error('Error fetching warehouses:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchWarehouses();
  }, []);

  useEffect(() => {
    let filtered = warehouses;

    // Search filter
    if (searchTerm.trim() !== '') {
      const searchLower = searchTerm.toLowerCase();
      filtered = filtered.filter(
        (warehouse) =>
          warehouse.name?.toLowerCase().includes(searchLower) ||
          warehouse.city?.toLowerCase().includes(searchLower) ||
          warehouse.district?.toLowerCase().includes(searchLower) ||
          warehouse.address?.toLowerCase().includes(searchLower),
      );
    }

    // City filter
    if (cityFilter !== 'all') {
      filtered = filtered.filter((warehouse) => warehouse.city === cityFilter);
    }

    setFilteredWarehouses(filtered);
  }, [searchTerm, cityFilter, warehouses]);

  // Get unique cities for filter dropdown
  const uniqueCities = Array.from(
    new Set(warehouses.map((warehouse) => warehouse.city).filter(Boolean)),
  ).sort();

  return (
    <div>
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6 sm:mb-8">
        <div className="min-w-0">
          <h1 className="text-2xl sm:text-3xl font-bold gradient-text mb-1 sm:mb-2">Depolar</h1>
          <p className="text-gray-600 dark:text-gray-400 text-sm sm:text-base">Depo yönetimi ve oda takibi</p>
        </div>
        <button
          onClick={() => setIsAddModalOpen(true)}
          className="btn-primary inline-flex items-center justify-center px-4 py-2.5 sm:px-6 sm:py-3 text-sm sm:text-base shrink-0 w-full sm:w-auto"
        >
          <PlusIcon className="h-4 w-4 sm:h-5 sm:w-5 mr-1.5 sm:mr-2 shrink-0" />
          <span>Yeni Depo</span>
        </button>
      </div>

      <AddWarehouseModal
        isOpen={isAddModalOpen}
        onClose={() => setIsAddModalOpen(false)}
        onSuccess={fetchWarehouses}
      />

      <EditWarehouseModal
        isOpen={isEditModalOpen}
        onClose={() => {
          setIsEditModalOpen(false);
          setSelectedWarehouse(null);
        }}
        onSuccess={fetchWarehouses}
        warehouse={selectedWarehouse}
      />

      {/* Filtreleme ve Arama */}
      <div className="mb-4 space-y-4">
        <div className="relative">
          <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
          </div>
          <input
            type="text"
            placeholder="Depo ara (isim, şehir, ilçe, adres)..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
          />
        </div>
        <div className="flex gap-4 flex-wrap">
          <div className="flex items-center gap-2">
            <FunnelIcon className="h-5 w-5 text-gray-400" />
            <select
              value={cityFilter}
              onChange={(e) => setCityFilter(e.target.value)}
              className="block px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
            >
              <option value="all">Tüm Şehirler</option>
              {uniqueCities.map((city) => (
                <option key={city} value={city}>
                  {city}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {deleteTarget && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          <div className="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div
              className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
              onClick={() => {
                if (!deleteLoading) {
                  setDeleteTarget(null);
                  setDeleteError('');
                }
              }}
            />
            <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
              <div className="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                    Depoyu Sil
                  </h3>
                  <button
                    onClick={() => {
                      if (!deleteLoading) {
                        setDeleteTarget(null);
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
                <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                  <strong className="text-gray-900 dark:text-white">{deleteTarget.name}</strong> depoyu
                  silmek istediğinize emin misiniz? Bu depoya ait odalar da silinecektir.
                </p>
                <div className="flex justify-end space-x-3">
                  <button
                    type="button"
                    onClick={() => {
                      if (!deleteLoading) {
                        setDeleteTarget(null);
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
                        await warehousesApi.remove(deleteTarget.id);
                        setDeleteTarget(null);
                        fetchWarehouses();
                      } catch (err: any) {
                        setDeleteError(
                          err.response?.data?.message || 'Depo silinirken bir hata oluştu',
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
              </div>
            </div>
          </div>
        </div>
      )}

      {loading ? (
        <div className="modern-card p-8 text-center">
          <p className="text-gray-600 dark:text-gray-400">Yükleniyor...</p>
        </div>
      ) : filteredWarehouses.length === 0 ? (
        <div className="modern-card p-8 text-center">
          <p className="text-gray-600 dark:text-gray-400">
            {(() => {
              if (warehouses.length === 0) {
                return 'Henüz depo bulunmamaktadır.';
              }
              return 'Arama kriterlerinize uygun depo bulunamadı.';
            })()}
          </p>
        </div>
      ) : (
        <div className="space-y-4">
          {filteredWarehouses.map((warehouse) => {
            const isExpanded = expandedWarehouses.has(warehouse.id);
            const rooms = warehouse.rooms || [];
            // Sadece aktif sözleşmesi olan odaları "dolu" olarak say
            const occupiedRooms = rooms.filter((r: any) => {
              const activeContracts = r.contracts?.filter((c: any) => c.is_active) || [];
              return activeContracts.length > 0;
            });

            return (
              <div
                key={warehouse.id}
                className="modern-card-gradient overflow-hidden"
              >
                <div className="p-6 relative">
                  <div className="absolute top-6 right-6 flex items-center gap-2">
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        setSelectedWarehouse(warehouse);
                        setIsEditModalOpen(true);
                      }}
                      className="text-gray-400 hover:text-primary-600 dark:hover:text-primary-400"
                      title="Düzenle"
                    >
                      <PencilIcon className="h-5 w-5" />
                    </button>
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        setDeleteError('');
                        setDeleteTarget(warehouse);
                      }}
                      className="text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                      title="Sil"
                    >
                      <TrashIcon className="h-5 w-5" />
                    </button>
                  </div>
                  <div className="flex items-center justify-between">
                    <div className="flex items-center flex-1">
                      <BuildingOfficeIcon className="h-8 w-8 text-primary-500 mr-3" />
                      <div>
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                          {warehouse.name}
                        </h3>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                          {warehouse.city} - {warehouse.district}
                        </p>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                          {warehouse.address}
                        </p>
                      </div>
                    </div>
                    <div className="text-right mr-8">
                      <p className="text-sm text-gray-600 dark:text-gray-300">
                        {rooms.length} Oda
                      </p>
                      <p className="text-xs text-gray-500 dark:text-gray-400">
                        {occupiedRooms.length} Dolu
                      </p>
                    </div>
                    <button
                      onClick={() => {
                        const newExpanded = new Set(expandedWarehouses);
                        if (isExpanded) {
                          newExpanded.delete(warehouse.id);
                        } else {
                          newExpanded.add(warehouse.id);
                        }
                        setExpandedWarehouses(newExpanded);
                      }}
                      className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                      {isExpanded ? (
                        <ChevronUpIcon className="h-5 w-5" />
                      ) : (
                        <ChevronDownIcon className="h-5 w-5" />
                      )}
                    </button>
                  </div>
                </div>

                {isExpanded && (
                  <div className="border-t border-gray-200 dark:border-gray-700 p-6">
                    <h4 className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                      Odalar ve Müşteriler
                    </h4>
                    {rooms.length === 0 ? (
                      <p className="text-sm text-gray-500 dark:text-gray-400">
                        Bu depoda henüz oda bulunmamaktadır.
                      </p>
                    ) : (
                      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {rooms.map((room: any) => {
                          const activeContracts = room.contracts?.filter(
                            (c: any) => c.is_active,
                          ) || [];
                          // Gerçek durumu belirle: aktif sözleşme varsa "Dolu", yoksa "Boş"
                          const actualStatus = activeContracts.length > 0 ? 'occupied' : 'empty';
                          
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
                            <div
                              key={room.id}
                              className="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:border-primary-300 dark:hover:border-primary-600 cursor-pointer transition-colors"
                              onClick={() => navigate(paths.odaDetay(room.id))}
                            >
                              <div className="flex items-center justify-between mb-2">
                                <div className="flex items-center">
                                  <CubeIcon className="h-4 w-4 text-primary-500 mr-2" />
                                  <span className="text-sm font-medium text-gray-900 dark:text-white">
                                    {room.room_number}
                                  </span>
                                </div>
                                <span
                                  className={`px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(
                                    actualStatus,
                                  )}`}
                                >
                                  {getStatusText(actualStatus)}
                                </span>
                              </div>
                              <div className="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                {formatTurkishNumber(Number(room.area_m2), 2)} m² - {formatTurkishCurrency(Number(room.monthly_price))}/ay
                              </div>
                              {activeContracts.length > 0 ? (
                                <div className="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                  <p className="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                    Müşteriler:
                                  </p>
                                  {activeContracts.map((contract: any) => (
                                    <div
                                      key={contract.id}
                                      className="flex items-center text-xs text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                      <UsersIcon className="h-3 w-3 mr-1 text-primary-500" />
                                      <span>
                                        {contract.customer?.first_name} {contract.customer?.last_name}
                                      </span>
                                    </div>
                                  ))}
                                </div>
                              ) : (
                                <div className="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                  <p className="text-xs text-gray-500 dark:text-gray-400">
                                    Müşteri yok
                                  </p>
                                </div>
                              )}
                            </div>
                          );
                        })}
                      </div>
                    )}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
