import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import JsBarcode from 'jsbarcode';

const trToEn = (text: string) => {
  return text
    .replace(/ğ/g, 'g')
    .replace(/Ğ/g, 'G')
    .replace(/ü/g, 'u')
    .replace(/Ü/g, 'U')
    .replace(/ş/g, 's')
    .replace(/Ş/g, 'S')
    .replace(/ı/g, 'i')
    .replace(/İ/g, 'I')
    .replace(/ö/g, 'o')
    .replace(/Ö/g, 'O')
    .replace(/ç/g, 'c')
    .replace(/Ç/g, 'C');
};

export const generateCustomerBarcodePDF = async (customer: any, items: any[]) => {
  const doc = new jsPDF({ orientation: 'landscape' });
  
  const fullName = trToEn(`${customer.first_name} ${customer.last_name}`);
  const phone = trToEn(customer.phone || 'Girilmemis');
  const email = trToEn(customer.email || 'Girilmemis');

  // Title
  doc.setFontSize(20);
  doc.text(trToEn('Müşteri Depo Etiketi'), 148, 15, { align: 'center' }); // Centered horizontally (297/2 approx)

  // Barcode
  const canvas = document.createElement('canvas');
  JsBarcode(canvas, customer.id.substring(0, 8).toUpperCase(), {
    format: 'CODE128',
    width: 2,
    height: 40,
    displayValue: true
  });
  const barcodeData = canvas.toDataURL('image/png');
  doc.addImage(barcodeData, 'PNG', 118, 20, 60, 20); // Centered under title

  // Customer Info Section
  doc.setFontSize(12);
  doc.setFont('helvetica', 'bold');
  doc.text(trToEn('Müşteri Bilgileri'), 14, 55);
  doc.setFont('helvetica', 'normal');
  doc.text(trToEn(`Depo Kiralayan: ${fullName}`), 14, 62);
  doc.text(trToEn(`İletişim: ${phone} / ${email}`), 14, 69);

  // Items Section
  doc.setFont('helvetica', 'bold');
  doc.text(trToEn('Eşya Listesi'), 14, 80);
  
  if (items && items.length > 0) {
    const tableData = items.map((item, index) => [
      index + 1,
      trToEn(item.name),
      item.quantity || 1,
      trToEn(item.unit || 'adet'),
      trToEn(item.description || '-')
    ]);

    autoTable(doc, {
      startY: 85,
      head: [['#', 'Esya Adi', 'Adet', 'Birim', 'Aciklama'].map(trToEn)],
      body: tableData,
      theme: 'grid',
      headStyles: { fillColor: [16, 185, 129] }, // emerald-500
      styles: { font: 'helvetica', fontSize: 10 },
    });
  } else {
    doc.setFont('helvetica', 'normal');
    doc.text(trToEn('Henüz eşya listesi girilmemiştir.'), 14, 85);
  }

  // Payments Section
  const lastY = (doc as any).lastAutoTable?.finalY || 95;
  doc.setFont('helvetica', 'bold');
  doc.text(trToEn('Aylık Ödeme Takip Çizelgesi'), 14, lastY + 15);

  // Generate 12-24 months of payment tracking
  const currentYear = new Date().getFullYear();
  const monthNames = [
    'Ocak', 'Subat', 'Mart', 'Nisan', 'Mayis', 'Haziran',
    'Temmuz', 'Agustos', 'Eylul', 'Ekim', 'Kasim', 'Aralik'
  ];

  const paymentData = [];
  for (let i = 0; i < 12; i++) {
    const monthName = monthNames[i];
    paymentData.push([
      trToEn(`${monthName} ${currentYear}`),
      '[  ]', // Paid checkbox
      '[  ]', // Unpaid checkbox
      ''      // Notes
    ]);
  }

  autoTable(doc, {
    startY: lastY + 20,
    head: [['Donem', 'Odendi', 'Odenmedi', 'Not'].map(trToEn)],
    body: paymentData,
    theme: 'grid',
    headStyles: { fillColor: [59, 130, 246] }, // blue-500
    styles: { font: 'helvetica', fontSize: 10 },
    columnStyles: {
      1: { halign: 'center', cellWidth: 25 },
      2: { halign: 'center', cellWidth: 25 },
    }
  });

  // Footer
  const pageCount = doc.internal.pages.length - 1;
  doc.setFontSize(8);
  for (let i = 1; i <= pageCount; i++) {
    doc.setPage(i);
    doc.text(
      trToEn(`Olusturulma Tarihi: ${new Date().toLocaleString('tr-TR')} - Sayfa ${i}/${pageCount}`),
      148,
      200, // Bottom of landscape A4
      { align: 'center' }
    );
  }

  doc.save(`etiket_${trToEn(customer.first_name)}_${trToEn(customer.last_name)}.pdf`);
};

/** Firma bilgisi (logo, ad, adres vb.) - teklif/sözleşme çıktısı için */
export type CompanyForPdf = {
  name?: string;
  address?: string;
  phone?: string;
  email?: string;
  logo_url?: string;
} | null;

/**
 * Teklif PDF'i - sözleşme sayfasına benzer profesyonel tasarım, firma bilgileri ve logo.
 */
export const generateProposalPDF = async (proposal: any, company?: CompanyForPdf) => {
  const doc = new jsPDF();
  const trToEn = (t: string) =>
    (t || '')
      .replace(/ğ/g, 'g').replace(/Ğ/g, 'G').replace(/ü/g, 'u').replace(/Ü/g, 'U')
      .replace(/ş/g, 's').replace(/Ş/g, 'S').replace(/ı/g, 'i').replace(/İ/g, 'I')
      .replace(/ö/g, 'o').replace(/Ö/g, 'O').replace(/ç/g, 'c').replace(/Ç/g, 'C');

  const pageW = doc.internal.pageSize.getWidth();
  let startY = 20;

  // Üst bölüm: Sol - TEKLİF başlığı; Sağ - Firma bilgileri (logo + ad/adres/tel/email)
  doc.setFontSize(22);
  doc.setFont('helvetica', 'bold');
  doc.text(trToEn('TEKLİF'), 14, startY);
  doc.setFontSize(10);
  doc.setFont('helvetica', 'normal');
  doc.text(trToEn(proposal.title || 'Teklif'), 14, startY + 8);
  doc.text(trToEn(`Tarih: ${new Date(proposal.created_at).toLocaleDateString('tr-TR')}`), 14, startY + 14);

  // Sağ taraf: Firma bilgileri
  const companyName = company?.name || 'DEPOPAZAR';
  const companyAddr = company?.address || 'Eşya Depolama Hizmetleri';
  let rightY = startY;
  if (company?.logo_url) {
    try {
      const baseUrl = typeof window !== 'undefined' ? window.location.origin : '';
      const imgUrl = `${baseUrl}/api/uploads/${company.logo_url}`;
      const res = await fetch(imgUrl, { credentials: 'include' });
      if (res.ok) {
        const blob = await res.blob();
        const dataUrl = await new Promise<string>((resolve, reject) => {
          const r = new FileReader();
          r.onload = () => resolve(r.result as string);
          r.onerror = reject;
          r.readAsDataURL(blob);
        });
        doc.addImage(dataUrl, 'PNG', pageW - 50, rightY - 8, 24, 24);
        rightY += 20;
      }
    } catch {
      // Logo yüklenemezse devam et
    }
  }
  doc.setFont('helvetica', 'bold');
  doc.text(trToEn(companyName), pageW - 14, rightY, { align: 'right' });
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(9);
  const addrLines = doc.splitTextToSize(trToEn(companyAddr), 70);
  doc.text(addrLines, pageW - 14, rightY + 6, { align: 'right' });
  rightY += 6 + addrLines.length * 5;
  if (company?.phone) {
    doc.text(trToEn(`Tel: ${company.phone}`), pageW - 14, rightY, { align: 'right' });
    rightY += 5;
  }
  if (company?.email) {
    doc.text(trToEn(`Email: ${company.email}`), pageW - 14, rightY, { align: 'right' });
  }

  startY = Math.max(startY + 22, rightY + 10);

  // Müşteri bilgisi
  doc.setFontSize(10);
  if (proposal.customer) {
    doc.setFont('helvetica', 'bold');
    doc.text(trToEn('Müşteri'), 14, startY);
    doc.setFont('helvetica', 'normal');
    doc.text(trToEn(`${proposal.customer.first_name || ''} ${proposal.customer.last_name || ''}`), 14, startY + 6);
    if (proposal.customer.email) doc.text(trToEn(proposal.customer.email), 14, startY + 12);
    if (proposal.customer.phone) doc.text(trToEn(proposal.customer.phone), 14, startY + 18);
    startY += 26;
  }

  // Kalem tablosu
  const items = proposal.items || [];
  const tableData = items.map((it: any) => [
    trToEn(it.name || '-'),
    String(it.quantity || 0),
    String(it.unit_price || 0),
    String(it.total_price || 0),
  ]);
  if (tableData.length > 0) {
    autoTable(doc, {
      startY,
      head: [['Hizmet/Ürün', 'Miktar', 'Birim Fiyat', 'Toplam'].map(trToEn)],
      body: tableData,
      theme: 'grid',
      headStyles: { fillColor: [16, 185, 129] },
      styles: { font: 'helvetica', fontSize: 9 },
    });
  }
  let y = (doc as any).lastAutoTable?.finalY ?? startY + 15;
  doc.setFont('helvetica', 'bold');
  doc.text(trToEn(`Toplam: ${proposal.currency || 'TRY'} ${Number(proposal.total_amount || 0).toLocaleString('tr-TR')} ₺`), 14, y + 10);
  y += 14;
  if (proposal.transport_terms) {
    doc.setFont('helvetica', 'bold');
    doc.text(trToEn('Taşıma Şartları'), 14, y + 4);
    doc.setFont('helvetica', 'normal');
    const terms = doc.splitTextToSize(trToEn(proposal.transport_terms), pageW - 28);
    doc.text(terms, 14, y + 10);
  }
  doc.save(`teklif_${proposal.id?.slice(0, 8) || 'teklif'}.pdf`);
};
