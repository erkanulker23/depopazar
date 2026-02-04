<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

define('APP_ROOT', dirname(__DIR__));
$config = require APP_ROOT . '/config/config.php';
date_default_timezone_set($config['timezone'] ?? 'Europe/Istanbul');

$pdo = require APP_ROOT . '/config/db.php';

require APP_ROOT . '/app/helpers.php';
require APP_ROOT . '/app/Auth.php';
require APP_ROOT . '/app/Router.php';
require APP_ROOT . '/app/models/User.php';
require APP_ROOT . '/app/models/Company.php';
require APP_ROOT . '/app/models/Warehouse.php';
require APP_ROOT . '/app/models/Room.php';
require APP_ROOT . '/app/models/Customer.php';
require APP_ROOT . '/app/models/Contract.php';
require APP_ROOT . '/app/models/Payment.php';
require APP_ROOT . '/app/models/TransportationJob.php';
require APP_ROOT . '/app/models/Service.php';
require APP_ROOT . '/app/models/ServiceCategory.php';
require APP_ROOT . '/app/models/Proposal.php';
require APP_ROOT . '/app/models/ProposalItem.php';
require APP_ROOT . '/app/models/BankAccount.php';
require APP_ROOT . '/app/models/Item.php';
require APP_ROOT . '/app/models/Notification.php';
require APP_ROOT . '/app/controllers/AuthController.php';
require APP_ROOT . '/app/controllers/DashboardController.php';
require APP_ROOT . '/app/controllers/WarehousesController.php';
require APP_ROOT . '/app/controllers/RoomsController.php';
require APP_ROOT . '/app/controllers/CustomersController.php';
require APP_ROOT . '/app/controllers/ContractsController.php';
require APP_ROOT . '/app/controllers/TransportationJobsController.php';
require APP_ROOT . '/app/controllers/UsersController.php';
require APP_ROOT . '/app/controllers/PaymentsController.php';
require APP_ROOT . '/app/controllers/ServicesController.php';
require APP_ROOT . '/app/controllers/ProposalsController.php';
require APP_ROOT . '/app/controllers/PermissionsController.php';
require APP_ROOT . '/app/controllers/PlaceholderController.php';
require APP_ROOT . '/app/controllers/SettingsController.php';
require APP_ROOT . '/app/controllers/ReportsController.php';
require APP_ROOT . '/app/controllers/NotificationsController.php';

Auth::init();
$router = new Router();

$router->get('/giris', fn() => (new AuthController($pdo))->showLogin());
$router->post('/giris', fn() => (new AuthController($pdo))->login());
$router->get('/cikis', fn() => (new AuthController($pdo))->logout());
$router->post('/cikis', fn() => (new AuthController($pdo))->logout());

$router->get('/genel-bakis', fn() => (new DashboardController($pdo))->index());

$router->get('/depolar', fn() => (new WarehousesController($pdo))->index());
$router->get('/depolar/{id}', fn(array $p) => (new WarehousesController($pdo))->detail($p));
$router->post('/depolar/ekle', fn() => (new WarehousesController($pdo))->create());
$router->post('/depolar/guncelle', fn() => (new WarehousesController($pdo))->update());
$router->post('/depolar/sil', fn() => (new WarehousesController($pdo))->delete());

$router->get('/odalar', fn() => (new RoomsController($pdo))->index());
$router->get('/odalar/{id}', fn(array $p) => (new RoomsController($pdo))->detail($p));
$router->post('/odalar/ekle', fn() => (new RoomsController($pdo))->create());
$router->post('/odalar/guncelle', fn() => (new RoomsController($pdo))->update());
$router->post('/odalar/sil', fn() => (new RoomsController($pdo))->delete());

$router->get('/musteri/genel-bakis', fn() => PlaceholderController::page('Müşteri Paneli', 'Hoş geldiniz.'));

$router->get('/musteriler', fn() => (new CustomersController($pdo))->index());
$router->get('/musteriler/excel-disari-aktar', fn() => (new CustomersController($pdo))->exportCsv());
$router->get('/musteriler/excel-sablon', fn() => (new CustomersController($pdo))->downloadTemplate());
$router->get('/musteriler/excel-ice-aktar', fn() => (new CustomersController($pdo))->importForm());
$router->post('/musteriler/excel-ice-aktar', fn() => (new CustomersController($pdo))->importCsv());
$router->post('/musteriler/ekle', fn() => (new CustomersController($pdo))->create());
$router->get('/musteriler/{id}/satir-detay', fn(array $p) => (new CustomersController($pdo))->rowFragment($p));
$router->get('/musteriler/{id}/yazdir', fn(array $p) => (new CustomersController($pdo))->printPage($p));
$router->get('/musteriler/{id}/barkod', fn(array $p) => (new CustomersController($pdo))->barcode($p));
$router->get('/musteriler/{id}', fn(array $p) => (new CustomersController($pdo))->show($p));
$router->get('/girisler', fn() => (new ContractsController($pdo))->index());
$router->get('/girisler/yeni', function () { Auth::requireStaff(); header('Location: /girisler?newSale=1'); exit; });
$router->get('/girisler/{id}/yazdir', fn(array $p) => (new ContractsController($pdo))->printPage($p));
$router->get('/girisler/{id}/duzenle', fn(array $p) => (new ContractsController($pdo))->edit($p));
$router->post('/girisler/guncelle', fn() => (new ContractsController($pdo))->update());
$router->get('/girisler/{id}', fn(array $p) => (new ContractsController($pdo))->show($p));
$router->post('/girisler/ekle', fn() => (new ContractsController($pdo))->create());
$router->post('/girisler/sonlandir', fn() => (new ContractsController($pdo))->terminate());
$router->post('/girisler/sil', fn() => (new ContractsController($pdo))->delete());
$router->get('/odemeler', fn() => (new PaymentsController($pdo))->index());
$router->post('/odemeler/odeme-al', fn() => (new PaymentsController($pdo))->markPaid());
$router->get('/odemeler/{id}/yazdir', fn(array $p) => (new PaymentsController($pdo))->printPage($p));
$router->get('/odemeler/{id}', fn(array $p) => (new PaymentsController($pdo))->show($p));
$router->get('/nakliye-isler', fn() => (new TransportationJobsController($pdo))->index());
$router->get('/nakliye-isler/{id}', fn(array $p) => (new TransportationJobsController($pdo))->show($p));
$router->get('/nakliye-isler/{id}/duzenle', fn(array $p) => (new TransportationJobsController($pdo))->edit($p));
$router->post('/nakliye-isler/ekle', fn() => (new TransportationJobsController($pdo))->create());
$router->post('/nakliye-isler/guncelle', fn() => (new TransportationJobsController($pdo))->update());
$router->post('/nakliye-isler/sil', fn() => (new TransportationJobsController($pdo))->delete());
$router->get('/hizmetler', fn() => (new ServicesController($pdo))->index());
$router->post('/hizmetler/kategori/ekle', fn() => (new ServicesController($pdo))->addCategory());
$router->post('/hizmetler/kategori/guncelle', fn() => (new ServicesController($pdo))->updateCategory());
$router->post('/hizmetler/kategori/sil', fn() => (new ServicesController($pdo))->deleteCategory());
$router->post('/hizmetler/hizmet/ekle', fn() => (new ServicesController($pdo))->addService());
$router->post('/hizmetler/hizmet/guncelle', fn() => (new ServicesController($pdo))->updateService());
$router->post('/hizmetler/hizmet/sil', fn() => (new ServicesController($pdo))->deleteService());
$router->get('/teklifler', fn() => (new ProposalsController($pdo))->index());
$router->get('/teklifler/yazdir', fn() => (new ProposalsController($pdo))->printPage());
$router->get('/teklifler/yeni', fn() => (new ProposalsController($pdo))->newForm());
$router->get('/teklifler/{id}/yazdir', fn(array $p) => (new ProposalsController($pdo))->printOne($p));
$router->get('/teklifler/{id}/duzenle', fn(array $p) => (new ProposalsController($pdo))->editForm($p));
$router->post('/teklifler/ekle', fn() => (new ProposalsController($pdo))->create());
$router->post('/teklifler/guncelle', fn() => (new ProposalsController($pdo))->update());
$router->post('/teklifler/durum', fn() => (new ProposalsController($pdo))->updateStatus());
$router->post('/teklifler/sil', fn() => (new ProposalsController($pdo))->delete());
$router->get('/kullanicilar', fn() => (new UsersController($pdo))->index());
$router->get('/kullanicilar/{id}/duzenle', fn(array $p) => (new UsersController($pdo))->editForm($p));
$router->post('/kullanicilar/ekle', fn() => (new UsersController($pdo))->create());
$router->post('/kullanicilar/guncelle', fn() => (new UsersController($pdo))->update());
$router->post('/kullanicilar/sifre-degistir', fn() => (new UsersController($pdo))->changePassword());
$router->post('/kullanicilar/sil', fn() => (new UsersController($pdo))->delete());
$router->get('/kullanicilar/{id}', fn(array $p) => (new UsersController($pdo))->show($p));
$router->get('/yetkiler', fn() => (new PermissionsController())->index());
$router->get('/raporlar', fn() => (new ReportsController($pdo))->index());
$router->get('/raporlar/banka-hesaplari', fn() => (new ReportsController($pdo))->bankAccountPayments());
$router->get('/ayarlar', fn() => (new SettingsController($pdo))->index());
$router->post('/ayarlar/firma-guncelle', fn() => (new SettingsController($pdo))->updateCompany());
$router->post('/ayarlar/banka-ekle', fn() => (new SettingsController($pdo))->createBankAccount());
$router->post('/ayarlar/banka-guncelle', fn() => (new SettingsController($pdo))->updateBankAccount());
$router->post('/ayarlar/banka-sil', fn() => (new SettingsController($pdo))->deleteBankAccount());
$router->post('/ayarlar/eposta-guncelle', fn() => (new SettingsController($pdo))->updateMailSettings());
$router->post('/ayarlar/eposta-test', fn() => (new SettingsController($pdo))->testEmail());
$router->post('/ayarlar/sablonlar-guncelle', fn() => (new SettingsController($pdo))->updateEmailTemplates());
$router->post('/ayarlar/paytr-guncelle', fn() => (new SettingsController($pdo))->updatePaytrSettings());
$router->get('/bildirimler', fn() => (new NotificationsController($pdo))->index());
$router->get('/api/bildirimler', fn() => (new NotificationsController($pdo))->apiList());
$router->post('/bildirimler/okundu', fn() => (new NotificationsController($pdo))->markAllRead());
$router->post('/bildirimler/tumunu-sil', fn() => (new NotificationsController($pdo))->deleteAll());

$router->get('/', function () {
    if (!Auth::isAuthenticated()) { header('Location: /giris'); exit; }
    header('Location: ' . (Auth::isCustomer() ? '/musteri/genel-bakis' : '/genel-bakis'));
    exit;
});

$router->run();
