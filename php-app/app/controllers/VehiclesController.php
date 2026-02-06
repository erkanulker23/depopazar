<?php
class VehiclesController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(): void
    {
        Auth::requireStaff();
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);

        $vehicles = [];
        $reportRows = [];
        $upcomingKasko = [];
        $upcomingInspection = [];
        $tableExists = $this->vehiclesTableExists();

        if ($tableExists) {
            try {
                $vehicles = Vehicle::findAll($this->pdo, $companyId);
            } catch (Throwable $e) {
                $this->logError('VehiclesController::index findAll', $e);
            }
        }

        try {
            $contractCountByPlate = $this->getContractCountByPlate($companyId);
            $jobCountByPlate = $this->getJobCountByPlate($companyId);
        } catch (Throwable $e) {
            $this->logError('VehiclesController::index report', $e);
            $contractCountByPlate = [];
            $jobCountByPlate = [];
        }

        $platesFromVehicles = array_map(fn($v) => Vehicle::normalizePlate($v['plate']), $vehicles);
        $allPlates = array_unique(array_merge(
            $platesFromVehicles,
            array_keys($contractCountByPlate),
            array_keys($jobCountByPlate)
        ));
        $vehicleByPlate = [];
        foreach ($vehicles as $v) {
            $key = Vehicle::normalizePlate($v['plate']);
            $vehicleByPlate[$key] = $v;
        }
        foreach ($allPlates as $plate) {
            $vehicle = $vehicleByPlate[$plate] ?? null;
            $contractCount = $contractCountByPlate[$plate] ?? 0;
            $jobCount = $jobCountByPlate[$plate] ?? 0;
            $reportRows[] = [
                'id' => $vehicle['id'] ?? null,
                'plate' => $plate,
                'model_year' => $vehicle['model_year'] ?? null,
                'kasko_date' => $vehicle['kasko_date'] ?? null,
                'inspection_date' => $vehicle['inspection_date'] ?? null,
                'cargo_volume_m3' => $vehicle['cargo_volume_m3'] ?? null,
                'notes' => $vehicle['notes'] ?? null,
                'transport_job_count' => $jobCount,
                'contract_count' => $contractCount,
                'total_nakliye_count' => $jobCount + $contractCount,
            ];
        }
        usort($reportRows, fn($a, $b) => strcmp($a['plate'], $b['plate']));

        $daysAlert = 30;
        $today = date('Y-m-d');
        foreach ($reportRows as $row) {
            if (!empty($row['kasko_date'])) {
                $diff = (strtotime($row['kasko_date']) - strtotime($today)) / 86400;
                if ($diff >= 0 && $diff <= $daysAlert) {
                    $upcomingKasko[] = $row;
                }
            }
            if (!empty($row['inspection_date'])) {
                $diff = (strtotime($row['inspection_date']) - strtotime($today)) / 86400;
                if ($diff >= 0 && $diff <= $daysAlert) {
                    $upcomingInspection[] = $row;
                }
            }
        }

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $pageTitle = 'Araçlar';
        require __DIR__ . '/../../views/vehicles/index.php';
    }

    public function create(): void
    {
        Auth::requireStaff();
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        if (!$companyId) {
            $_SESSION['flash_error'] = 'Araç eklemek için şirket seçili olmalı.';
            http_response_code(303);
            header('Location: /araclar');
            exit;
        }
        if (!$this->vehiclesTableExists()) {
            $_SESSION['flash_error'] = 'Araçlar tablosu bulunamadı. Lütfen php-app/sql/migrations/01_add_vehicles_table.sql çalıştırın.';
            http_response_code(303);
            header('Location: /araclar');
            exit;
        }
        $plate = Vehicle::normalizePlate($_POST['plate'] ?? '');
        if ($plate === '') {
            $_SESSION['flash_error'] = 'Plaka girin.';
            http_response_code(303);
            header('Location: /araclar');
            exit;
        }
        try {
            Vehicle::create($this->pdo, [
                'company_id' => $companyId,
                'plate' => $plate,
                'model_year' => $_POST['model_year'] ?? null,
                'kasko_date' => !empty($_POST['kasko_date']) ? $_POST['kasko_date'] : null,
                'inspection_date' => !empty($_POST['inspection_date']) ? $_POST['inspection_date'] : null,
                'cargo_volume_m3' => isset($_POST['cargo_volume_m3']) && $_POST['cargo_volume_m3'] !== '' ? $_POST['cargo_volume_m3'] : null,
                'notes' => trim($_POST['notes'] ?? '') ?: null,
            ]);
        } catch (Throwable $e) {
            $this->logError('VehiclesController::create', $e);
            $_SESSION['flash_error'] = $this->friendlyVehicleError($e, $plate, 'ekleme');
            http_response_code(303);
            header('Location: /araclar');
            exit;
        }
        $_SESSION['flash_success'] = 'Araç eklendi.';
        http_response_code(303);
        header('Location: /araclar');
        exit;
    }

    public function update(): void
    {
        Auth::requireStaff();
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $id = trim($_POST['id'] ?? '');
        if ($id === '') {
            $_SESSION['flash_error'] = 'Araç bulunamadı.';
            http_response_code(303);
            header('Location: /araclar');
            exit;
        }
        $plate = Vehicle::normalizePlate($_POST['plate'] ?? '');
        if ($plate === '') {
            $_SESSION['flash_error'] = 'Plaka girin.';
            http_response_code(303);
            header('Location: /araclar');
            exit;
        }
        try {
            $ok = Vehicle::update($this->pdo, $id, [
                'plate' => $plate,
                'model_year' => $_POST['model_year'] ?? null,
                'kasko_date' => !empty($_POST['kasko_date']) ? $_POST['kasko_date'] : null,
                'inspection_date' => !empty($_POST['inspection_date']) ? $_POST['inspection_date'] : null,
                'cargo_volume_m3' => isset($_POST['cargo_volume_m3']) && $_POST['cargo_volume_m3'] !== '' ? $_POST['cargo_volume_m3'] : null,
                'notes' => trim($_POST['notes'] ?? '') ?: null,
            ], $companyId);
            if ($ok) {
                $_SESSION['flash_success'] = 'Araç güncellendi.';
            } else {
                $_SESSION['flash_error'] = 'Araç güncellenemedi veya yetkiniz yok.';
            }
        } catch (Throwable $e) {
            $this->logError('VehiclesController::update', $e);
            $_SESSION['flash_error'] = $this->friendlyVehicleError($e, $plate, 'güncelleme');
        }
        http_response_code(303);
        header('Location: /araclar');
        exit;
    }

    public function delete(): void
    {
        Auth::requireStaff();
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $id = trim($_POST['id'] ?? '');
        if ($id === '') {
            $_SESSION['flash_error'] = 'Araç bulunamadı.';
            http_response_code(303);
            header('Location: /araclar');
            exit;
        }
        $ok = Vehicle::delete($this->pdo, $id, $companyId);
        if ($ok) {
            $_SESSION['flash_success'] = 'Araç kaydı silindi.';
        } else {
            $_SESSION['flash_error'] = 'Araç silinemedi veya yetkiniz yok.';
        }
        http_response_code(303);
        header('Location: /araclar');
        exit;
    }

    public function show(array $params): void
    {
        Auth::requireStaff();
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $id = $params['id'] ?? '';
        if ($id === '') {
            header('Location: /araclar');
            exit;
        }
        $vehicle = Vehicle::findById($this->pdo, $id, $companyId);
        if (!$vehicle) {
            $_SESSION['flash_error'] = 'Araç bulunamadı.';
            header('Location: /araclar');
            exit;
        }
        $trafficInsurances = [];
        $kaskos = [];
        $accidents = [];
        if ($this->vehiclesTableExists()) {
            try {
                $trafficInsurances = $this->tablesExist('vehicle_traffic_insurances') ? VehicleTrafficInsurance::findByVehicle($this->pdo, $id) : [];
            } catch (Throwable $e) {
                $this->logError('VehiclesController::show traffic', $e);
            }
            try {
                $kaskos = $this->tablesExist('vehicle_kaskos') ? VehicleKasko::findByVehicle($this->pdo, $id) : [];
            } catch (Throwable $e) {
                $this->logError('VehiclesController::show kasko', $e);
            }
            try {
                $accidents = $this->tablesExist('vehicle_accidents') ? VehicleAccident::findByVehicle($this->pdo, $id) : [];
            } catch (Throwable $e) {
                $this->logError('VehiclesController::show accidents', $e);
            }
        }
        $trafficInsuranceDocs = [];
        $kaskoDocs = [];
        $accidentDocs = [];
        if ($this->tablesExist('vehicle_traffic_insurance_documents')) {
            foreach ($trafficInsurances as $ti) {
                $trafficInsuranceDocs[$ti['id']] = VehicleTrafficInsuranceDocument::findByTrafficInsuranceId($this->pdo, $ti['id']);
            }
        }
        if ($this->tablesExist('vehicle_kasko_documents')) {
            foreach ($kaskos as $k) {
                $kaskoDocs[$k['id']] = VehicleKaskoDocument::findByKaskoId($this->pdo, $k['id']);
            }
        }
        if ($this->tablesExist('vehicle_accident_documents')) {
            foreach ($accidents as $a) {
                $accidentDocs[$a['id']] = VehicleAccidentDocument::findByAccidentId($this->pdo, $a['id']);
            }
        }
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        $pageTitle = 'Araç: ' . $vehicle['plate'];
        require __DIR__ . '/../../views/vehicles/show.php';
    }

    public function addTrafficInsurance(): void
    {
        Auth::requireStaff();
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $vehicleId = trim($_POST['vehicle_id'] ?? '');
        if ($vehicleId === '') {
            $_SESSION['flash_error'] = 'Araç bulunamadı.';
            header('Location: /araclar');
            exit;
        }
        $vehicle = Vehicle::findById($this->pdo, $vehicleId, $companyId);
        if (!$vehicle) {
            $_SESSION['flash_error'] = 'Araç bulunamadı veya yetkiniz yok.';
            header('Location: /araclar');
            exit;
        }
        try {
            $tid = VehicleTrafficInsurance::create($this->pdo, [
                'vehicle_id' => $vehicleId,
                'policy_number' => $_POST['policy_number'] ?? null,
                'insurer_name' => $_POST['insurer_name'] ?? null,
                'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
                'end_date' => $_POST['end_date'] ?? date('Y-m-d'),
                'notes' => $_POST['notes'] ?? null,
            ]);
            if (!empty($_FILES['document']['tmp_name']) && ($_FILES['document']['error'] ?? 0) === UPLOAD_ERR_OK) {
                $info = $this->saveUploadedVehicleDoc('traffic_insurance', $tid);
                if ($info) {
                    VehicleTrafficInsuranceDocument::create($this->pdo, [
                        'traffic_insurance_id' => $tid,
                        'file_path' => $info['file_path'],
                        'file_name' => $info['file_name'],
                        'file_size' => $info['file_size'],
                        'mime_type' => $info['mime_type'],
                    ]);
                }
            }
            $_SESSION['flash_success'] = 'Trafik sigortası eklendi.';
        } catch (Throwable $e) {
            $this->logError('VehiclesController::addTrafficInsurance', $e);
            $_SESSION['flash_error'] = 'Eklenemedi: ' . $e->getMessage();
        }
        header('Location: /araclar/' . $vehicleId);
        exit;
    }

    public function updateTrafficInsurance(): void
    {
        $this->redirectVehicleId(null, function (string $vehicleId) {
            $ok = VehicleTrafficInsurance::update($this->pdo, trim($_POST['id'] ?? ''), [
                'policy_number' => $_POST['policy_number'] ?? null,
                'insurer_name' => $_POST['insurer_name'] ?? null,
                'start_date' => $_POST['start_date'] ?? null,
                'end_date' => $_POST['end_date'] ?? null,
                'notes' => $_POST['notes'] ?? null,
            ], $vehicleId);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'Trafik sigortası güncellendi.' : 'Güncellenemedi.';
        });
    }

    public function deleteTrafficInsurance(): void
    {
        $this->redirectVehicleId('Trafik sigortası silindi.', null, function (string $vehicleId) {
            return VehicleTrafficInsurance::delete($this->pdo, trim($_POST['id'] ?? ''), $vehicleId);
        });
    }

    public function addKasko(): void
    {
        Auth::requireStaff();
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $vehicleId = trim($_POST['vehicle_id'] ?? '');
        if ($vehicleId === '') {
            $_SESSION['flash_error'] = 'Araç bulunamadı.';
            header('Location: /araclar');
            exit;
        }
        $vehicle = Vehicle::findById($this->pdo, $vehicleId, $companyId);
        if (!$vehicle) {
            $_SESSION['flash_error'] = 'Araç bulunamadı veya yetkiniz yok.';
            header('Location: /araclar');
            exit;
        }
        try {
            $kid = VehicleKasko::create($this->pdo, [
                'vehicle_id' => $vehicleId,
                'policy_number' => $_POST['policy_number'] ?? null,
                'insurer_name' => $_POST['insurer_name'] ?? null,
                'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
                'end_date' => $_POST['end_date'] ?? date('Y-m-d'),
                'premium_amount' => $_POST['premium_amount'] ?? null,
                'notes' => $_POST['notes'] ?? null,
            ]);
            if (!empty($_FILES['document']['tmp_name']) && ($_FILES['document']['error'] ?? 0) === UPLOAD_ERR_OK) {
                $info = $this->saveUploadedVehicleDoc('kasko', $kid);
                if ($info) {
                    VehicleKaskoDocument::create($this->pdo, [
                        'kasko_id' => $kid,
                        'file_path' => $info['file_path'],
                        'file_name' => $info['file_name'],
                        'file_size' => $info['file_size'],
                        'mime_type' => $info['mime_type'],
                    ]);
                }
            }
            $_SESSION['flash_success'] = 'Kasko eklendi.';
        } catch (Throwable $e) {
            $this->logError('VehiclesController::addKasko', $e);
            $_SESSION['flash_error'] = 'Eklenemedi: ' . $e->getMessage();
        }
        header('Location: /araclar/' . $vehicleId);
        exit;
    }

    public function updateKasko(): void
    {
        $this->redirectVehicleId(null, function (string $vehicleId) {
            $ok = VehicleKasko::update($this->pdo, trim($_POST['id'] ?? ''), [
                'policy_number' => $_POST['policy_number'] ?? null,
                'insurer_name' => $_POST['insurer_name'] ?? null,
                'start_date' => $_POST['start_date'] ?? null,
                'end_date' => $_POST['end_date'] ?? null,
                'premium_amount' => $_POST['premium_amount'] ?? null,
                'notes' => $_POST['notes'] ?? null,
            ], $vehicleId);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'Kasko güncellendi.' : 'Güncellenemedi.';
        });
    }

    public function deleteKasko(): void
    {
        $this->redirectVehicleId('Kasko silindi.', null, function (string $vehicleId) {
            return VehicleKasko::delete($this->pdo, trim($_POST['id'] ?? ''), $vehicleId);
        });
    }

    public function addAccident(): void
    {
        $this->redirectVehicleId('Kaza kaydı eklendi.', function (string $vehicleId) {
            VehicleAccident::create($this->pdo, [
                'vehicle_id' => $vehicleId,
                'accident_date' => $_POST['accident_date'] ?? date('Y-m-d'),
                'description' => $_POST['description'] ?? null,
                'damage_info' => $_POST['damage_info'] ?? null,
                'repair_cost' => $_POST['repair_cost'] ?? null,
                'notes' => $_POST['notes'] ?? null,
            ]);
        });
    }

    public function updateAccident(): void
    {
        $this->redirectVehicleId(null, function (string $vehicleId) {
            $ok = VehicleAccident::update($this->pdo, trim($_POST['id'] ?? ''), [
                'accident_date' => $_POST['accident_date'] ?? null,
                'description' => $_POST['description'] ?? null,
                'damage_info' => $_POST['damage_info'] ?? null,
                'repair_cost' => $_POST['repair_cost'] ?? null,
                'notes' => $_POST['notes'] ?? null,
            ], $vehicleId);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'Kaza kaydı güncellendi.' : 'Güncellenemedi.';
        });
    }

    public function deleteAccident(): void
    {
        $this->redirectVehicleId('Kaza kaydı silindi.', null, function (string $vehicleId) {
            return VehicleAccident::delete($this->pdo, trim($_POST['id'] ?? ''), $vehicleId);
        });
    }

    public function uploadTrafficInsuranceDocument(): void
    {
        $this->uploadVehicleDoc('traffic_insurance', function (string $vehicleId, array $vehicle) {
            $tid = trim($_POST['traffic_insurance_id'] ?? '');
            if ($tid === '') {
                $_SESSION['flash_error'] = 'Trafik sigortası seçilmedi.';
                return null;
            }
            $ti = VehicleTrafficInsurance::findById($this->pdo, $tid, $vehicleId);
            if (!$ti) {
                $_SESSION['flash_error'] = 'Trafik sigortası bulunamadı.';
                return null;
            }
            $info = $this->saveUploadedVehicleDoc('traffic_insurance', $tid);
            if (!$info) return null;
            VehicleTrafficInsuranceDocument::create($this->pdo, [
                'traffic_insurance_id' => $tid,
                'file_path' => $info['file_path'],
                'file_name' => $info['file_name'],
                'file_size' => $info['file_size'],
                'mime_type' => $info['mime_type'],
            ]);
            $_SESSION['flash_success'] = 'Belge eklendi.';
            return $vehicleId;
        });
    }

    public function deleteTrafficInsuranceDocument(): void
    {
        $this->deleteVehicleDoc('traffic_insurance', function (string $docId) {
            $doc = VehicleTrafficInsuranceDocument::findOne($this->pdo, $docId);
            return $doc ? VehicleTrafficInsuranceDocument::softDelete($this->pdo, $docId) : false;
        });
    }

    public function uploadKaskoDocument(): void
    {
        $this->uploadVehicleDoc('kasko', function (string $vehicleId, array $vehicle) {
            $kid = trim($_POST['kasko_id'] ?? '');
            if ($kid === '') {
                $_SESSION['flash_error'] = 'Kasko seçilmedi.';
                return null;
            }
            $k = VehicleKasko::findById($this->pdo, $kid, $vehicleId);
            if (!$k) {
                $_SESSION['flash_error'] = 'Kasko bulunamadı.';
                return null;
            }
            $info = $this->saveUploadedVehicleDoc('kasko', $kid);
            if (!$info) return null;
            VehicleKaskoDocument::create($this->pdo, [
                'kasko_id' => $kid,
                'file_path' => $info['file_path'],
                'file_name' => $info['file_name'],
                'file_size' => $info['file_size'],
                'mime_type' => $info['mime_type'],
            ]);
            $_SESSION['flash_success'] = 'Belge eklendi.';
            return $vehicleId;
        });
    }

    public function deleteKaskoDocument(): void
    {
        $this->deleteVehicleDoc('kasko', function (string $docId) {
            $doc = VehicleKaskoDocument::findOne($this->pdo, $docId);
            return $doc ? VehicleKaskoDocument::softDelete($this->pdo, $docId) : false;
        });
    }

    public function uploadAccidentDocument(): void
    {
        $this->uploadVehicleDoc('accident', function (string $vehicleId, array $vehicle) {
            $aid = trim($_POST['accident_id'] ?? '');
            if ($aid === '') {
                $_SESSION['flash_error'] = 'Kaza kaydı seçilmedi.';
                return null;
            }
            $a = VehicleAccident::findById($this->pdo, $aid, $vehicleId);
            if (!$a) {
                $_SESSION['flash_error'] = 'Kaza kaydı bulunamadı.';
                return null;
            }
            $kind = in_array($_POST['document_kind'] ?? '', ['ruhsat', 'kimlik', 'kaza_foto', 'diger'], true) ? $_POST['document_kind'] : 'diger';
            $info = $this->saveUploadedVehicleDoc('accident', $aid);
            if (!$info) return null;
            VehicleAccidentDocument::create($this->pdo, [
                'accident_id' => $aid,
                'document_kind' => $kind,
                'file_path' => $info['file_path'],
                'file_name' => $info['file_name'],
                'file_size' => $info['file_size'],
                'mime_type' => $info['mime_type'],
            ]);
            $_SESSION['flash_success'] = 'Belge eklendi.';
            return $vehicleId;
        });
    }

    public function deleteAccidentDocument(): void
    {
        $this->deleteVehicleDoc('accident', function (string $docId) {
            $doc = VehicleAccidentDocument::findOne($this->pdo, $docId);
            return $doc ? VehicleAccidentDocument::softDelete($this->pdo, $docId) : false;
        });
    }

    private function uploadVehicleDoc(string $subdir, callable $doUpload): void
    {
        Auth::requireStaff();
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $vehicleId = trim($_POST['vehicle_id'] ?? '');
        if ($vehicleId === '') {
            $_SESSION['flash_error'] = 'Araç bulunamadı.';
            header('Location: /araclar');
            exit;
        }
        $vehicle = Vehicle::findById($this->pdo, $vehicleId, $companyId);
        if (!$vehicle) {
            $_SESSION['flash_error'] = 'Araç bulunamadı veya yetkiniz yok.';
            header('Location: /araclar');
            exit;
        }
        $redirectId = $doUpload($vehicleId, $vehicle);
        header('Location: /araclar/' . ($redirectId ?? $vehicleId));
        exit;
    }

    private function saveUploadedVehicleDoc(string $subdir, string $parentId): ?array
    {
        $file = $_FILES['document'] ?? null;
        if (!$file || ($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'Lütfen bir dosya seçin (PDF veya resim).';
            return null;
        }
        $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            $_SESSION['flash_error'] = 'İzin verilen formatlar: ' . implode(', ', $allowedExt);
            return null;
        }
        $baseDir = defined('APP_ROOT') ? APP_ROOT . '/public/uploads/vehicle_docs' : __DIR__ . '/../../public/uploads/vehicle_docs';
        $dir = $baseDir . '/' . preg_replace('/[^a-z_]/', '', $subdir);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $filename = $parentId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $path = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            $_SESSION['flash_error'] = 'Dosya kaydedilemedi.';
            return null;
        }
        $relativePath = '/uploads/vehicle_docs/' . preg_replace('/[^a-z_]/', '', $subdir) . '/' . $filename;
        return [
            'file_path' => $relativePath,
            'file_name' => $file['name'],
            'file_size' => (int) ($file['size'] ?? 0),
            'mime_type' => $file['type'] ?? null,
        ];
    }

    private function deleteVehicleDoc(string $type, callable $doDelete): void
    {
        Auth::requireStaff();
        $docId = trim($_POST['id'] ?? '');
        $vehicleId = trim($_POST['vehicle_id'] ?? '');
        if ($docId === '' || $vehicleId === '') {
            $_SESSION['flash_error'] = 'Eksik parametre.';
            header('Location: /araclar' . ($vehicleId ? '/' . $vehicleId : ''));
            exit;
        }
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $vehicle = Vehicle::findById($this->pdo, $vehicleId, $companyId);
        if (!$vehicle) {
            $_SESSION['flash_error'] = 'Yetkisiz.';
            header('Location: /araclar');
            exit;
        }
        $ok = $doDelete($docId);
        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'Belge silindi.' : 'Belge silinemedi.';
        header('Location: /araclar/' . $vehicleId);
        exit;
    }

    /** @param callable(string):void $onSuccess @param callable(string):bool|null $onDelete */
    private function redirectVehicleId(?string $successMessage, ?callable $onSuccess = null, ?callable $onDelete = null): void
    {
        Auth::requireStaff();
        $user = Auth::user();
        $companyId = Company::getCompanyIdForUser($this->pdo, $user);
        $vehicleId = trim($_POST['vehicle_id'] ?? '');
        if ($vehicleId === '') {
            $_SESSION['flash_error'] = 'Araç bulunamadı.';
            header('Location: /araclar');
            exit;
        }
        $vehicle = Vehicle::findById($this->pdo, $vehicleId, $companyId);
        if (!$vehicle) {
            $_SESSION['flash_error'] = 'Araç bulunamadı veya yetkiniz yok.';
            header('Location: /araclar');
            exit;
        }
        if ($onDelete !== null) {
            $ok = $onDelete($vehicleId);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? $successMessage : 'Silinemedi.';
        } elseif ($onSuccess !== null) {
            try {
                $onSuccess($vehicleId);
                if ($successMessage !== null) {
                    $_SESSION['flash_success'] = $successMessage;
                }
            } catch (Throwable $e) {
                $this->logError('VehiclesController redirectVehicleId', $e);
                $_SESSION['flash_error'] = 'İşlem yapılamadı: ' . $e->getMessage();
            }
        }
        header('Location: /araclar/' . $vehicleId);
        exit;
    }

    private function tablesExist(string $table): bool
    {
        try {
            $this->pdo->query('SELECT 1 FROM ' . preg_replace('/[^a-z0-9_]/', '', $table) . ' LIMIT 1');
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function vehiclesTableExists(): bool
    {
        try {
            $this->pdo->query('SELECT 1 FROM vehicles LIMIT 1');
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function logError(string $context, Throwable $e): void
    {
        $logDir = defined('APP_ROOT') ? (APP_ROOT . '/storage/logs') : (__DIR__ . '/../../storage/logs');
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/php-errors.log';
        $line = date('Y-m-d H:i:s') . ' ' . $context . ' ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString() . "\n";
        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }

    /** Duplicate plate (uq_vehicles_company_plate) vb. hatalar için kullanıcı dostu mesaj. */
    private function friendlyVehicleError(Throwable $e, string $plate, string $action): string
    {
        if ($e instanceof \PDOException && $e->getCode() === '23000') {
            $msg = $e->getMessage();
            if (strpos($msg, '1062') !== false && (strpos($msg, 'uq_vehicles_company_plate') !== false || strpos($msg, 'Duplicate entry') !== false)) {
                return sprintf('Bu plaka (%s) zaten kayıtlı. Farklı bir plaka girin veya listeden mevcut aracı düzenleyin.', $plate);
            }
        }
        return 'Araç ' . $action . ' yapılamadı: ' . ($e->getMessage() ?: 'Bilinmeyen hata');
    }

    /** @return array<string, int> */
    private function getContractCountByPlate(?string $companyId): array
    {
        $sql = 'SELECT c.vehicle_plate FROM contracts c
                INNER JOIN rooms r ON r.id = c.room_id AND r.deleted_at IS NULL
                INNER JOIN warehouses w ON w.id = r.warehouse_id AND w.deleted_at IS NULL
                WHERE c.deleted_at IS NULL AND c.vehicle_plate IS NOT NULL AND c.vehicle_plate != "" ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND w.company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $countByPlate = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            foreach (Vehicle::parsePlatesFromField($row['vehicle_plate'] ?? '') as $plate) {
                $countByPlate[$plate] = ($countByPlate[$plate] ?? 0) + 1;
            }
        }
        return $countByPlate;
    }

    /** @return array<string, int> */
    private function getJobCountByPlate(?string $companyId): array
    {
        $sql = 'SELECT vehicle_plate FROM transportation_jobs WHERE deleted_at IS NULL AND vehicle_plate IS NOT NULL AND vehicle_plate != "" ';
        $params = [];
        if ($companyId) {
            $sql .= ' AND company_id = ? ';
            $params[] = $companyId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $countByPlate = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            foreach (Vehicle::parsePlatesFromField($row['vehicle_plate'] ?? '') as $plate) {
                $countByPlate[$plate] = ($countByPlate[$plate] ?? 0) + 1;
            }
        }
        return $countByPlate;
    }
}
