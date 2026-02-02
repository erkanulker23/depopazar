import * as XLSX from 'xlsx';

export interface CustomerExcelRow {
  'Ad': string;
  'Soyad': string;
  'E-posta': string;
  'Telefon': string;
  'TC Kimlik No': string;
  'Adres': string;
  'Notlar': string;
  'Aktif Sözleşme Sayısı': number;
  'Toplam Borç': number;
}

export function exportCustomersToExcel(customers: any[]) {
  const data: CustomerExcelRow[] = customers.map((customer) => {
    const activeContracts = customer.contracts?.filter((c: any) => c.is_active) || [];
    const totalDebt = activeContracts.reduce((sum: number, contract: any) => {
      const unpaidPayments = contract.payments?.filter(
        (p: any) => p.status === 'pending' || p.status === 'overdue',
      ) || [];
      return sum + unpaidPayments.reduce((s: number, p: any) => s + Number(p.amount), 0);
    }, 0);

    return {
      'Ad': customer.first_name || '',
      'Soyad': customer.last_name || '',
      'E-posta': customer.email || '',
      'Telefon': customer.phone || '',
      'TC Kimlik No': customer.identity_number || '',
      'Adres': customer.address || '',
      'Notlar': customer.notes || '',
      'Aktif Sözleşme Sayısı': activeContracts.length,
      'Toplam Borç': totalDebt,
    };
  });

  const worksheet = XLSX.utils.json_to_sheet(data);
  const workbook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(workbook, worksheet, 'Müşteriler');

  // Column widths
  worksheet['!cols'] = [
    { wch: 15 }, // Ad
    { wch: 15 }, // Soyad
    { wch: 25 }, // E-posta
    { wch: 15 }, // Telefon
    { wch: 15 }, // TC Kimlik No
    { wch: 30 }, // Adres
    { wch: 30 }, // Notlar
    { wch: 20 }, // Aktif Sözleşme Sayısı
    { wch: 15 }, // Toplam Borç
  ];

  const fileName = `musteriler_${new Date().toISOString().split('T')[0]}.xlsx`;
  XLSX.writeFile(workbook, fileName);
}

/** Banka Hesap Raporu Excel satırı: hangi bankaya ne kadar, ne zaman, kimden */
export function exportBankAccountReportToExcel(reportData: {
  bank_accounts: Array<{
    bank_account: {
      bank_name: string;
      account_number: string;
      account_holder_name: string;
      iban?: string | null;
      branch_name?: string | null;
    } | null;
    customers: Array<{
      customer: { first_name: string; last_name: string; email?: string; phone?: string | null } | null;
      payments: Array<{
        payment_number: string;
        contract_number: string;
        amount: number;
        paid_at: string;
        transaction_id?: string | null;
        payment_method?: string | null;
        notes?: string | null;
      }>;
    }>;
  }>;
}) {
  const rows: Array<{
    'Banka Adı': string;
    'Hesap No': string;
    'Hesap Sahibi': string;
    IBAN: string;
    Şube: string;
    'Müşteri Adı': string;
    'Müşteri Soyadı': string;
    'Müşteri E-posta': string;
    'Müşteri Telefon': string;
    'Ödeme No': string;
    'Sözleşme No': string;
    Tutar: number;
    'Ödeme Tarihi': string;
    'İşlem No': string;
    'Ödeme Yöntemi': string;
    Notlar: string;
  }> = [];

  for (const ba of reportData.bank_accounts) {
    const bankName = ba.bank_account?.bank_name ?? '';
    const accountNumber = ba.bank_account?.account_number ?? '';
    const accountHolder = ba.bank_account?.account_holder_name ?? '';
    const iban = ba.bank_account?.iban ?? '';
    const branch = ba.bank_account?.branch_name ?? '';

    for (const cust of ba.customers) {
      const customerFirstName = cust.customer?.first_name ?? '';
      const customerLastName = cust.customer?.last_name ?? '';
      const customerEmail = cust.customer?.email ?? '';
      const customerPhone = cust.customer?.phone ?? '';

      for (const p of cust.payments) {
        rows.push({
          'Banka Adı': bankName,
          'Hesap No': accountNumber,
          'Hesap Sahibi': accountHolder,
          IBAN: iban,
          Şube: branch,
          'Müşteri Adı': customerFirstName,
          'Müşteri Soyadı': customerLastName,
          'Müşteri E-posta': customerEmail,
          'Müşteri Telefon': customerPhone,
          'Ödeme No': p.payment_number,
          'Sözleşme No': p.contract_number ?? '',
          Tutar: Number(p.amount),
          'Ödeme Tarihi': p.paid_at ? new Date(p.paid_at).toLocaleString('tr-TR') : '',
          'İşlem No': p.transaction_id ?? '',
          'Ödeme Yöntemi': p.payment_method ?? '',
          Notlar: p.notes ?? '',
        });
      }
    }
  }

  const worksheet = XLSX.utils.json_to_sheet(rows);
  const workbook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(workbook, worksheet, 'Banka Hesap Raporu');
  worksheet['!cols'] = [
    { wch: 20 }, { wch: 18 }, { wch: 22 }, { wch: 28 }, { wch: 15 },
    { wch: 15 }, { wch: 15 }, { wch: 25 }, { wch: 15 },
    { wch: 18 }, { wch: 18 }, { wch: 12 }, { wch: 18 }, { wch: 18 }, { wch: 15 }, { wch: 25 },
  ];
  const fileName = `banka_hesap_raporu_${new Date().toISOString().split('T')[0]}.xlsx`;
  XLSX.writeFile(workbook, fileName);
}

export function importCustomersFromExcel(file: File): Promise<CustomerExcelRow[]> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();

    reader.onload = (e) => {
      try {
        const data = new Uint8Array(e.target?.result as ArrayBuffer);
        const workbook = XLSX.read(data, { type: 'array' });
        const firstSheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[firstSheetName];
        const jsonData = XLSX.utils.sheet_to_json<CustomerExcelRow>(worksheet);

        resolve(jsonData);
      } catch (error) {
        reject(error);
      }
    };

    reader.onerror = () => {
      reject(new Error('Dosya okunamadı'));
    };

    reader.readAsArrayBuffer(file);
  });
}
