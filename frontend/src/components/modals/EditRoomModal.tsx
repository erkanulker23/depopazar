import { useState, useEffect } from 'react';
import { XMarkIcon } from '@heroicons/react/24/outline';
import { roomsApi } from '../../services/api/roomsApi';
import { warehousesApi } from '../../services/api/warehousesApi';

const ROOM_STATUSES = [
  { value: 'empty', label: 'Boş' },
  { value: 'occupied', label: 'Dolu' },
  { value: 'reserved', label: 'Rezerve' },
  { value: 'locked', label: 'Kilitli' },
] as const;

interface EditRoomModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
  room: any;
}

export function EditRoomModal({
  isOpen,
  onClose,
  onSuccess,
  room,
}: EditRoomModalProps) {
  const [warehouses, setWarehouses] = useState<any[]>([]);
  const [formData, setFormData] = useState({
    room_number: '',
    warehouse_id: '',
    area_m2: '',
    monthly_price: '',
    status: 'empty',
    floor: '',
    block: '',
    corridor: '',
    description: '',
    notes: '',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    const fetchWarehouses = async () => {
      try {
        const data = await warehousesApi.getAll();
        setWarehouses(data);
      } catch (e) {
        console.error('Error fetching warehouses:', e);
      }
    };
    fetchWarehouses();
  }, []);

  useEffect(() => {
    if (room) {
      setFormData({
        room_number: room.room_number || '',
        warehouse_id: room.warehouse_id || room.warehouse?.id || '',
        area_m2: room.area_m2 != null ? String(room.area_m2) : '',
        monthly_price: room.monthly_price != null ? String(room.monthly_price) : '',
        status: room.status || 'empty',
        floor: room.floor || '',
        block: room.block || '',
        corridor: room.corridor || '',
        description: room.description || '',
        notes: room.notes || '',
      });
    }
  }, [room]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const payload = {
        room_number: formData.room_number.trim(),
        warehouse_id: formData.warehouse_id || undefined,
        area_m2: formData.area_m2 ? parseFloat(formData.area_m2) : undefined,
        monthly_price: formData.monthly_price ? parseFloat(formData.monthly_price) : undefined,
        status: formData.status,
        floor: formData.floor.trim() || null,
        block: formData.block.trim() || null,
        corridor: formData.corridor.trim() || null,
        description: formData.description.trim() || null,
        notes: formData.notes.trim() || null,
      };
      await roomsApi.update(room.id, payload);
      onSuccess();
      onClose();
    } catch (err: any) {
      setError(err.response?.data?.message || 'Oda güncellenirken bir hata oluştu');
    } finally {
      setLoading(false);
    }
  };

  const inputClass =
    'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white';
  const labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';

  if (!isOpen || !room) return null;

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div className="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div
          className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
          onClick={onClose}
        />
        <div className="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
          <div className="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-medium text-gray-900 dark:text-white">Oda Düzenle</h3>
              <button
                onClick={onClose}
                className="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
              >
                <XMarkIcon className="h-6 w-6" />
              </button>
            </div>

            {error && (
              <div className="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 p-3">
                <p className="text-sm text-red-800 dark:text-red-200">{error}</p>
              </div>
            )}

            <form onSubmit={handleSubmit} className="flex flex-col">
              <div className="max-h-[60vh] overflow-y-auto space-y-4 pr-1">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className={labelClass}>Oda No *</label>
                  <input
                    type="text"
                    required
                    value={formData.room_number}
                    onChange={(e) => setFormData({ ...formData, room_number: e.target.value })}
                    className={inputClass}
                    placeholder="Örn: A-101"
                  />
                </div>
                <div>
                  <label className={labelClass}>Depo *</label>
                  <select
                    required
                    value={formData.warehouse_id}
                    onChange={(e) => setFormData({ ...formData, warehouse_id: e.target.value })}
                    className={inputClass}
                  >
                    <option value="">Depo seçin</option>
                    {warehouses.map((w) => (
                      <option key={w.id} value={w.id}>
                        {w.name}
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className={labelClass}>Alan (m²) *</label>
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    value={formData.area_m2}
                    onChange={(e) => setFormData({ ...formData, area_m2: e.target.value })}
                    className={inputClass}
                  />
                </div>
                <div>
                  <label className={labelClass}>Aylık Fiyat (TL) *</label>
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    value={formData.monthly_price}
                    onChange={(e) => setFormData({ ...formData, monthly_price: e.target.value })}
                    className={inputClass}
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className={labelClass}>Durum *</label>
                  <select
                    required
                    value={formData.status}
                    onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                    className={inputClass}
                  >
                    {ROOM_STATUSES.map((s) => (
                      <option key={s.value} value={s.value}>
                        {s.label}
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className={labelClass}>Kat</label>
                  <input
                    type="text"
                    value={formData.floor}
                    onChange={(e) => setFormData({ ...formData, floor: e.target.value })}
                    className={inputClass}
                    placeholder="Örn: 1"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className={labelClass}>Blok</label>
                  <input
                    type="text"
                    value={formData.block}
                    onChange={(e) => setFormData({ ...formData, block: e.target.value })}
                    className={inputClass}
                    placeholder="Örn: A"
                  />
                </div>
                <div>
                  <label className={labelClass}>Koridor</label>
                  <input
                    type="text"
                    value={formData.corridor}
                    onChange={(e) => setFormData({ ...formData, corridor: e.target.value })}
                    className={inputClass}
                    placeholder="Örn: Kuzey"
                  />
                </div>
              </div>

              <div>
                <label className={labelClass}>Açıklama</label>
                <textarea
                  value={formData.description}
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                  rows={2}
                  className={inputClass}
                />
              </div>
              <div>
                <label className={labelClass}>Notlar</label>
                <textarea
                  value={formData.notes}
                  onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                  rows={2}
                  className={inputClass}
                />
              </div>
              </div>

              <div className="flex justify-end space-x-3 pt-4 mt-4 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
                <button
                  type="button"
                  onClick={onClose}
                  className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                  İptal
                </button>
                <button
                  type="submit"
                  disabled={loading}
                  className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
                >
                  {loading ? 'Kaydediliyor...' : 'Kaydet'}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  );
}
