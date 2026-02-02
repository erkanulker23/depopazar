import { Fragment, useState, useEffect } from 'react';
import { Dialog, Transition, RadioGroup } from '@headlessui/react';
import { XMarkIcon, PaperAirplaneIcon, DocumentTextIcon, ChatBubbleBottomCenterTextIcon, EnvelopeIcon } from '@heroicons/react/24/outline';
import { contractsApi } from '../../services/api/contractsApi';
import toast from 'react-hot-toast';

interface SendContractModalProps {
  isOpen: boolean;
  onClose: () => void;
  customer: any;
}

export function SendContractModal({ isOpen, onClose, customer }: SendContractModalProps) {
  const [loading, setLoading] = useState(false);
  const [contracts, setContracts] = useState<any[]>([]);
  const [selectedContractId, setSelectedContractId] = useState<string>('');
  const [sendMethod, setSendMethod] = useState<'email' | 'sms'>('email');

  useEffect(() => {
    if (isOpen && customer) {
      // Filter active contracts
      const activeContracts = customer.contracts?.filter((c: any) => c.is_active) || [];
      setContracts(activeContracts);
      if (activeContracts.length > 0) {
        setSelectedContractId(activeContracts[0].id);
      }
    }
  }, [isOpen, customer]);

  const handleSend = async () => {
    if (!selectedContractId) {
      toast.error('Lütfen bir sözleşme seçin');
      return;
    }

    if (sendMethod === 'sms' && !customer.phone) {
      toast.error('Müşterinin telefon numarası bulunamadı');
      return;
    }

    if (sendMethod === 'email' && !customer.email) {
      toast.error('Müşterinin e-posta adresi bulunamadı');
      return;
    }

    setLoading(true);
    try {
      await contractsApi.sendContract(selectedContractId, sendMethod);
      toast.success('Sözleşme başarıyla gönderildi');
      onClose();
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Gönderim sırasında bir hata oluştu');
    } finally {
      setLoading(false);
    }
  };

  return (
    <Transition.Root show={isOpen} as={Fragment}>
      <Dialog as="div" className="relative z-50" onClose={onClose}>
        <Transition.Child
          as={Fragment}
          enter="ease-out duration-300"
          enterFrom="opacity-0"
          enterTo="opacity-100"
          leave="ease-in duration-200"
          leaveFrom="opacity-100"
          leaveTo="opacity-0"
        >
          <div className="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
        </Transition.Child>

        <div className="fixed inset-0 z-10 overflow-y-auto">
          <div className="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <Transition.Child
              as={Fragment}
              enter="ease-out duration-300"
              enterFrom="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              enterTo="opacity-100 translate-y-0 sm:scale-100"
              leave="ease-in duration-200"
              leaveFrom="opacity-100 translate-y-0 sm:scale-100"
              leaveTo="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
              <Dialog.Panel className="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                <div className="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                  <button
                    type="button"
                    className="rounded-md bg-white dark:bg-gray-800 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                    onClick={onClose}
                  >
                    <span className="sr-only">Kapat</span>
                    <XMarkIcon className="h-6 w-6" aria-hidden="true" />
                  </button>
                </div>
                
                <div className="p-6">
                  <div className="flex items-center gap-4 mb-6">
                    <div className="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/30 sm:mx-0">
                      <PaperAirplaneIcon className="h-6 w-6 text-primary-600 dark:text-primary-400" aria-hidden="true" />
                    </div>
                    <div className="text-center sm:text-left">
                      <Dialog.Title as="h3" className="text-lg font-semibold leading-6 text-gray-900 dark:text-white">
                        Sözleşme Gönder
                      </Dialog.Title>
                      <div className="mt-1">
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                          {customer?.first_name} {customer?.last_name}
                        </p>
                      </div>
                    </div>
                  </div>

                  <div className="space-y-6">
                    {/* Contract Selection */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Sözleşme Seçin
                      </label>
                      {contracts.length === 0 ? (
                        <p className="text-sm text-red-500">Müşteriye ait aktif sözleşme bulunamadı.</p>
                      ) : (
                        <select
                          value={selectedContractId}
                          onChange={(e) => setSelectedContractId(e.target.value)}
                          className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-2 px-3"
                        >
                          {contracts.map((contract) => (
                            <option key={contract.id} value={contract.id}>
                              {contract.contract_number} - {contract.room?.room_number || 'Oda Yok'}
                            </option>
                          ))}
                        </select>
                      )}
                    </div>

                    {/* Method Selection */}
                    {contracts.length > 0 && (
                      <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                          Gönderim Yöntemi
                        </label>
                        <RadioGroup value={sendMethod} onChange={setSendMethod}>
                          <div className="grid grid-cols-2 gap-4">
                            <RadioGroup.Option
                              value="email"
                              className={({ checked }) =>
                                `cursor-pointer relative flex cursor-pointer rounded-lg px-5 py-4 shadow-md focus:outline-none border ${
                                  checked
                                    ? 'bg-primary-50 dark:bg-primary-900/20 border-primary-500 ring-2 ring-primary-500'
                                    : 'bg-white dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600'
                                }`
                              }
                            >
                              {({ checked }) => (
                                <div className="flex w-full items-center justify-between">
                                  <div className="flex items-center">
                                    <div className="text-sm">
                                      <RadioGroup.Label
                                        as="p"
                                        className={`font-medium ${
                                          checked ? 'text-primary-900 dark:text-primary-100' : 'text-gray-900 dark:text-white'
                                        }`}
                                      >
                                        <div className="flex items-center gap-2">
                                          <EnvelopeIcon className="h-5 w-5" />
                                          E-posta
                                        </div>
                                      </RadioGroup.Label>
                                      <RadioGroup.Description
                                        as="span"
                                        className={`inline ${
                                          checked ? 'text-primary-700 dark:text-primary-300' : 'text-gray-500 dark:text-gray-400'
                                        }`}
                                      >
                                        <span className="block mt-1 text-xs">{customer?.email || 'E-posta yok'}</span>
                                      </RadioGroup.Description>
                                    </div>
                                  </div>
                                </div>
                              )}
                            </RadioGroup.Option>

                            <RadioGroup.Option
                              value="sms"
                              className={({ checked }) =>
                                `cursor-pointer relative flex cursor-pointer rounded-lg px-5 py-4 shadow-md focus:outline-none border ${
                                  checked
                                    ? 'bg-primary-50 dark:bg-primary-900/20 border-primary-500 ring-2 ring-primary-500'
                                    : 'bg-white dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600'
                                }`
                              }
                            >
                              {({ checked }) => (
                                <div className="flex w-full items-center justify-between">
                                  <div className="flex items-center">
                                    <div className="text-sm">
                                      <RadioGroup.Label
                                        as="p"
                                        className={`font-medium ${
                                          checked ? 'text-primary-900 dark:text-primary-100' : 'text-gray-900 dark:text-white'
                                        }`}
                                      >
                                        <div className="flex items-center gap-2">
                                          <ChatBubbleBottomCenterTextIcon className="h-5 w-5" />
                                          SMS
                                        </div>
                                      </RadioGroup.Label>
                                      <RadioGroup.Description
                                        as="span"
                                        className={`inline ${
                                          checked ? 'text-primary-700 dark:text-primary-300' : 'text-gray-500 dark:text-gray-400'
                                        }`}
                                      >
                                        <span className="block mt-1 text-xs">{customer?.phone || 'Telefon yok'}</span>
                                      </RadioGroup.Description>
                                    </div>
                                  </div>
                                </div>
                              )}
                            </RadioGroup.Option>
                          </div>
                        </RadioGroup>
                      </div>
                    )}
                  </div>
                </div>

                <div className="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                  <button
                    type="button"
                    className="inline-flex w-full justify-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed"
                    onClick={handleSend}
                    disabled={loading || contracts.length === 0}
                  >
                    {loading ? (
                      <>
                        <div className="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                        Gönderiliyor...
                      </>
                    ) : (
                      'Gönder'
                    )}
                  </button>
                  <button
                    type="button"
                    className="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto"
                    onClick={onClose}
                    disabled={loading}
                  >
                    İptal
                  </button>
                </div>
              </Dialog.Panel>
            </Transition.Child>
          </div>
        </div>
      </Dialog>
    </Transition.Root>
  );
}
