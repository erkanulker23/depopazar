<?php

use Dompdf\Dompdf;
use Dompdf\Options;

class ContractPdf
{
    public static function filename(array $contract): string
    {
        $num = preg_replace('/[^a-zA-Z0-9_-]+/u', '-', (string) ($contract['contract_number'] ?? 'sozlesme'));
        $num = trim($num, '-');
        return 'Sozlesme-' . ($num !== '' ? $num : 'belge') . '.pdf';
    }

    /** @return resource|string */
    public static function stream(PDO $pdo, array $contract)
    {
        if (!empty($contract['contract_pdf_url'])) {
            $path = publicFilePath($contract['contract_pdf_url']);
            if ($path && is_file($path)) {
                return fopen($path, 'rb');
            }
        }
        return self::generateBinary($pdo, $contract);
    }

    public static function generateBinary(PDO $pdo, array $contract): string
    {
        $id = $contract['id'] ?? '';
        $payments = Payment::findByContractId($pdo, $id);
        $company = !empty($contract['company_id']) ? Company::findOne($pdo, $contract['company_id']) : null;
        $soldByName = trim(($contract['sold_by_first_name'] ?? '') . ' ' . ($contract['sold_by_last_name'] ?? '')) ?: '-';
        $items = Item::findByContractId($pdo, $id);

        ob_start();
        require __DIR__ . '/../views/contracts/pdf_document.php';
        $html = ob_get_clean();

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }

    public static function sendDownload(PDO $pdo, array $contract): void
    {
        $filename = self::filename($contract);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');

        if (!empty($contract['contract_pdf_url'])) {
            $path = publicFilePath($contract['contract_pdf_url']);
            if ($path && is_file($path)) {
                header('Content-Length: ' . (string) filesize($path));
                readfile($path);
                return;
            }
        }

        $binary = self::generateBinary($pdo, $contract);
        header('Content-Length: ' . (string) strlen($binary));
        echo $binary;
    }
}
