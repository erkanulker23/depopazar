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
  const doc = new jsPDF();
  
  const fullName = trToEn(`${customer.first_name} ${customer.last_name}`);
  const phone = trToEn(customer.phone || 'Girilmemis');
  const email = trToEn(customer.email || 'Girilmemis');

  // Title
  doc.setFontSize(20);
  doc.text(trToEn('Müşteri Depo Etiketi'), 105, 15, { align: 'center' });

  // Barcode
  const canvas = document.createElement('canvas');
  JsBarcode(canvas, customer.id.substring(0, 8).toUpperCase(), {
    format: 'CODE128',
    width: 2,
    height: 40,
    displayValue: true
  });
  const barcodeData = canvas.toDataURL('image/png');
  doc.addImage(barcodeData, 'PNG', 75, 20, 60, 20);

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
      105,
      285,
      { align: 'center' }
    );
  }

  doc.save(`etiket_${trToEn(customer.first_name)}_${trToEn(customer.last_name)}.pdf`);
};
