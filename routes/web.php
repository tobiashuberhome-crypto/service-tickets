<?php

use App\Http\Controllers\InternesTicketController;
use App\Http\Controllers\PortalAccountController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\EasyAppointmentsWebhookController;
use App\Http\Controllers\GeiserCustomerPortalController;
<<<<<<< HEAD
=======
use App\Http\Controllers\CibenaCustomerPortalController;
>>>>>>> old-ticket-system/main
use App\Http\Controllers\SchoolPortalController;
use App\Http\Controllers\CustomerPortalRequestController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\DolibarrLookupController;
use App\Http\Controllers\MachineDocumentController;
use App\Http\Controllers\ServiceDefaultController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\SparePartCategoryController;
use App\Http\Controllers\SparePartController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\TicketPartController;
use App\Http\Controllers\TicketMessageController;
use App\Http\Controllers\TicketServiceLineController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/tickets');
Route::redirect('/login', '/admin/login');

Route::post('/integrations/easyappointments/bookings', [EasyAppointmentsWebhookController::class, 'store']);

Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');


Route::get('/kundenportal', [CustomerPortalController::class, 'home'])->name('customer-portal.home');
Route::post('/kundenportal/anfrage', [CustomerPortalController::class, 'storeRequest'])->name('customer-portal.requests.store');
Route::get('/kundenportal/login', [CustomerPortalController::class, 'login'])->name('customer-portal.login');
Route::post('/kundenportal/login', [CustomerPortalController::class, 'sendMagicLink'])->name('customer-portal.magic.send');
Route::post('/kundenportal/login/password', [CustomerPortalController::class, 'loginWithPassword'])->name('customer-portal.password.login');
Route::get('/kundenportal/magic/{token}', [CustomerPortalController::class, 'consumeMagicLink'])->name('customer-portal.magic');
Route::post('/kundenportal/logout', [CustomerPortalController::class, 'logout'])->name('customer-portal.logout');
Route::middleware('customer.portal')->group(function (): void {
    Route::get('/kundenportal/uebersicht', [CustomerPortalController::class, 'dashboard'])->name('customer-portal.dashboard');
    Route::get('/kundenportal/tickets/neu', [CustomerPortalController::class, 'createTicket'])->name('customer-portal.tickets.create');
    Route::post('/kundenportal/tickets', [CustomerPortalController::class, 'storeTicket'])->name('customer-portal.tickets.store');
});

Route::prefix('/kundenportal/geiser')->group(function (): void {
    Route::get('/', [GeiserCustomerPortalController::class, 'home'])->name('geiser-portal.home');
    Route::get('/login', [GeiserCustomerPortalController::class, 'login'])->name('geiser-portal.login');
    Route::post('/login', [GeiserCustomerPortalController::class, 'sendMagicLink'])->name('geiser-portal.magic.send');
    Route::post('/login/password', [GeiserCustomerPortalController::class, 'loginWithPassword'])->name('geiser-portal.password.login');
    Route::post('/login/password/reset', [GeiserCustomerPortalController::class, 'sendPasswordResetLink'])->name('geiser-portal.password.reset.send');
    Route::get('/password/reset/{token}', [GeiserCustomerPortalController::class, 'showPasswordResetForm'])->name('geiser-portal.password.reset.form');
    Route::post('/password/reset/{token}', [GeiserCustomerPortalController::class, 'resetPassword'])->name('geiser-portal.password.reset');
    Route::get('/magic/{token}', [GeiserCustomerPortalController::class, 'consumeMagicLink'])->name('geiser-portal.magic');
    Route::post('/logout', [GeiserCustomerPortalController::class, 'logout'])->name('geiser-portal.logout');

    Route::middleware('customer.portal:geiser_customer_portal_account_id')->group(function (): void {
        Route::get('/uebersicht', [GeiserCustomerPortalController::class, 'dashboard'])->name('geiser-portal.dashboard');
        Route::get('/historie', [GeiserCustomerPortalController::class, 'history'])->name('geiser-portal.history');
        Route::get('/maschinenprofil', [GeiserCustomerPortalController::class, 'findMachineProfile'])->name('geiser-portal.machine-profiles.lookup');
        Route::get('/ticket-history', [GeiserCustomerPortalController::class, 'lookupTicketHistory'])->name('geiser-portal.ticket-history.lookup');
        Route::get('/tickets/neu', [GeiserCustomerPortalController::class, 'createTicket'])->name('geiser-portal.tickets.create');
        Route::post('/tickets', [GeiserCustomerPortalController::class, 'storeTicket'])->name('geiser-portal.tickets.store');
<<<<<<< HEAD
=======
        Route::post('/monatliche-rechnung', [GeiserCustomerPortalController::class, 'generateMonthlyInvoice'])->name('geiser-portal.monthly-invoice');
>>>>>>> old-ticket-system/main
        Route::get('/tickets/{ticket}/print', [GeiserCustomerPortalController::class, 'printTicket'])->name('geiser-portal.tickets.print');
        Route::get('/tickets/{ticket}/work-report', [GeiserCustomerPortalController::class, 'generateWorkReport'])->name('geiser-portal.tickets.work-report');
        Route::post('/tickets/{ticket}/work-report/send-mail', [GeiserCustomerPortalController::class, 'mailWorkReport'])->name('geiser-portal.tickets.work-report.mail');
        Route::get('/tickets/{ticket}', [GeiserCustomerPortalController::class, 'showTicket'])->name('geiser-portal.tickets.show');
        Route::put('/tickets/{ticket}', [GeiserCustomerPortalController::class, 'updateTicket'])->name('geiser-portal.tickets.update');
        Route::put('/tickets/{ticket}/machine-returned', [GeiserCustomerPortalController::class, 'updateMachineReturned'])->name('geiser-portal.tickets.machine-returned');
        Route::post('/tickets/{ticket}/messages', [TicketMessageController::class, 'storeGeiser'])->name('geiser-portal.tickets.messages.store');
        Route::get('/tickets/{ticket}/messages/{message}/attachments/{attachment}', [TicketMessageController::class, 'downloadGeiserAttachment'])->name('geiser-portal.tickets.messages.attachments.download');
        Route::post('/ocr-scan', [GeiserCustomerPortalController::class, 'scanTicketImage'])->name('geiser-portal.ocr.scan');
    });
});

<<<<<<< HEAD
=======
Route::prefix('/kundenportal/cibena')->group(function (): void {
    Route::get('/', [CibenaCustomerPortalController::class, 'home'])->name('cibena-portal.home');
    Route::get('/login', [CibenaCustomerPortalController::class, 'login'])->name('cibena-portal.login');
    Route::post('/login', [CibenaCustomerPortalController::class, 'sendMagicLink'])->name('cibena-portal.magic.send');
    Route::post('/login/password', [CibenaCustomerPortalController::class, 'loginWithPassword'])->name('cibena-portal.password.login');
    Route::post('/login/password/reset', [CibenaCustomerPortalController::class, 'sendPasswordResetLink'])->name('cibena-portal.password.reset.send');
    Route::get('/password/reset/{token}', [CibenaCustomerPortalController::class, 'showPasswordResetForm'])->name('cibena-portal.password.reset.form');
    Route::post('/password/reset/{token}', [CibenaCustomerPortalController::class, 'resetPassword'])->name('cibena-portal.password.reset');
    Route::get('/magic/{token}', [CibenaCustomerPortalController::class, 'consumeMagicLink'])->name('cibena-portal.magic');
    Route::post('/logout', [CibenaCustomerPortalController::class, 'logout'])->name('cibena-portal.logout');

    Route::middleware('customer.portal:cibena_customer_portal_account_id')->group(function (): void {
        Route::get('/uebersicht', [CibenaCustomerPortalController::class, 'dashboard'])->name('cibena-portal.dashboard');
        Route::get('/historie', [CibenaCustomerPortalController::class, 'history'])->name('cibena-portal.history');
        Route::get('/maschinenprofil', [CibenaCustomerPortalController::class, 'findMachineProfile'])->name('cibena-portal.machine-profiles.lookup');
        Route::get('/ticket-history', [CibenaCustomerPortalController::class, 'lookupTicketHistory'])->name('cibena-portal.ticket-history.lookup');
        Route::get('/tickets/neu', [CibenaCustomerPortalController::class, 'createTicket'])->name('cibena-portal.tickets.create');
        Route::post('/tickets', [CibenaCustomerPortalController::class, 'storeTicket'])->name('cibena-portal.tickets.store');
        Route::post('/monatliche-rechnung', [CibenaCustomerPortalController::class, 'generateMonthlyInvoice'])->name('cibena-portal.monthly-invoice');
        Route::get('/tickets/{ticket}/print', [CibenaCustomerPortalController::class, 'printTicket'])->name('cibena-portal.tickets.print');
        Route::get('/tickets/{ticket}/work-report', [CibenaCustomerPortalController::class, 'generateWorkReport'])->name('cibena-portal.tickets.work-report');
        Route::post('/tickets/{ticket}/work-report/send-mail', [CibenaCustomerPortalController::class, 'mailWorkReport'])->name('cibena-portal.tickets.work-report.mail');
        Route::get('/tickets/{ticket}', [CibenaCustomerPortalController::class, 'showTicket'])->name('cibena-portal.tickets.show');
        Route::put('/tickets/{ticket}', [CibenaCustomerPortalController::class, 'updateTicket'])->name('cibena-portal.tickets.update');
        Route::put('/tickets/{ticket}/machine-returned', [CibenaCustomerPortalController::class, 'updateMachineReturned'])->name('cibena-portal.tickets.machine-returned');
        Route::post('/tickets/{ticket}/messages', [TicketMessageController::class, 'storeGeiser'])->name('cibena-portal.tickets.messages.store');
        Route::get('/tickets/{ticket}/messages/{message}/attachments/{attachment}', [TicketMessageController::class, 'downloadGeiserAttachment'])->name('cibena-portal.tickets.messages.attachments.download');
        Route::post('/ocr-scan', [CibenaCustomerPortalController::class, 'scanTicketImage'])->name('cibena-portal.ocr.scan');
    });
});

>>>>>>> old-ticket-system/main
// ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ Schul-Portal ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬
Route::prefix('/schulportal')->group(function (): void {
    Route::get('/', [SchoolPortalController::class, 'home'])->name('school-portal.home');
    Route::get('/login', [SchoolPortalController::class, 'login'])->name('school-portal.login');
    Route::post('/login', [SchoolPortalController::class, 'sendMagicLink'])->name('school-portal.magic.send');
    Route::post('/login/password', [SchoolPortalController::class, 'loginWithPassword'])->name('school-portal.password.login');
    Route::get('/magic/{token}', [SchoolPortalController::class, 'consumeMagicLink'])->name('school-portal.magic');
    Route::post('/logout', [SchoolPortalController::class, 'logout'])->name('school-portal.logout');
    Route::get('/m/{token}', [SchoolPortalController::class, 'qrReportForm'])->name('school-portal.qr.form');
    Route::post('/m/{token}', [SchoolPortalController::class, 'qrReportSubmit'])->name('school-portal.qr.submit');

    Route::middleware('customer.portal:school_portal_account_id')->group(function (): void {
        Route::get('/uebersicht', [SchoolPortalController::class, 'dashboard'])->name('school-portal.dashboard');
        Route::post('/raeume', [SchoolPortalController::class, 'storeRoom'])->name('school-portal.rooms.store');
        Route::get('/raeume/{room}', [SchoolPortalController::class, 'showRoom'])->name('school-portal.rooms.show');
        Route::post('/raeume/{room}/maschinen', [SchoolPortalController::class, 'storeMachine'])->name('school-portal.machines.store');
        Route::get('/benutzer', [SchoolPortalController::class, 'users'])->name('school-portal.users.index');
        Route::post('/benutzer', [SchoolPortalController::class, 'storeUser'])->name('school-portal.users.store');
        Route::put('/benutzer/{user}', [SchoolPortalController::class, 'updateUser'])->name('school-portal.users.update');
        Route::get('/maschinen/{machine}', [SchoolPortalController::class, 'showMachine'])->name('school-portal.machines.show');
        Route::post('/maschinen/{machine}/qr-regenerate', [SchoolPortalController::class, 'regenerateMachineQr'])->name('school-portal.machines.qr-regenerate');
        Route::get('/maschinen/{machine}/ticket', [SchoolPortalController::class, 'createTicket'])->name('school-portal.tickets.create');
        Route::post('/maschinen/{machine}/ticket', [SchoolPortalController::class, 'storeTicket'])->name('school-portal.tickets.store');
    });
});

Route::middleware('admin.auth')->group(function (): void {
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::post('/tickets/delivery-note', [TicketController::class, 'generateDeliveryNote'])->name('tickets.delivery-note');
<<<<<<< HEAD
=======
    Route::post('/tickets/monthly-invoice', [TicketController::class, 'generateMonthlyInvoice'])->name('tickets.monthly-invoice');
>>>>>>> old-ticket-system/main
    Route::get('/tickets/{ticket}/geiser-invoice', [TicketController::class, 'generateGeiserInvoice'])->name('tickets.geiser-invoice');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
    Route::post('/tickets/{ticket}/complete', [TicketController::class, 'complete'])->name('tickets.complete');
    Route::post('/tickets/{ticket}/retry-sync', [TicketController::class, 'retrySync'])->name('tickets.retry-sync');
    Route::post('/tickets/{ticket}/messages', [TicketMessageController::class, 'storeAdmin'])->name('tickets.messages.store');
    Route::get('/tickets/{ticket}/messages/{message}/attachments/{attachment}', [TicketMessageController::class, 'downloadAdminAttachment'])->name('tickets.messages.attachments.download');
    Route::post('/tickets/{ticket}/activate-order', [TicketController::class, 'activateOrder'])->name('tickets.activate-order');
    Route::post('/tickets/{ticket}/close-order-invoice', [TicketController::class, 'closeOrderAndCreateInvoice'])->name('tickets.close-order-invoice');
    Route::post('/tickets/{ticket}/activate-invoice', [TicketController::class, 'activateInvoice'])->name('tickets.activate-invoice');
    Route::post('/tickets/reorder', [TicketController::class, 'reorder'])->name('tickets.reorder');

    Route::post('/tickets/{ticket}/parts', [TicketPartController::class, 'store'])->name('tickets.parts.store');
    Route::post('/tickets/{ticket}/manual-lines', [TicketPartController::class, 'storeManualLines'])->name('tickets.manual-lines.store');
    Route::post('/tickets/{ticket}/parts/scan', [TicketPartController::class, 'scanStore'])->name('tickets.parts.scan');
    Route::delete('/tickets/{ticket}/parts/{ticketPart}', [TicketPartController::class, 'destroy'])->name('tickets.parts.destroy');
    Route::put('/tickets/{ticket}/service-lines/{ticketServiceLine}', [TicketServiceLineController::class, 'update'])->name('tickets.service-lines.update');

    Route::get('/lookup/customers', [DolibarrLookupController::class, 'customers'])->name('lookup.customers');
    Route::post('/lookup/customers', [DolibarrLookupController::class, 'createCustomer'])->name('lookup.customers.create');
    Route::get('/lookup/manufacturers', [DolibarrLookupController::class, 'manufacturers'])->name('lookup.manufacturers');
    Route::get('/lookup/part-categories', [\App\Http\Controllers\SparePartCategoryController::class, 'listJson'])->name('lookup.part-categories');
    Route::get('/lookup/machines', [DolibarrLookupController::class, 'machines'])->name('lookup.machines');
    Route::post('/lookup/machines', [DolibarrLookupController::class, 'createMachine'])->name('lookup.machines.create');
    Route::get('/lookup/ticket-history', [HistoryController::class, 'lookupSerialHistory'])->name('lookup.ticket-history');

    Route::get('/kundenanfragen', [CustomerPortalRequestController::class, 'index'])->name('customer-portal-requests.index');
    Route::get('/kundenanfragen/{customerPortalRequest}', [CustomerPortalRequestController::class, 'show'])->name('customer-portal-requests.show');
    Route::post('/kundenanfragen/{customerPortalRequest}/verknuepfen', [CustomerPortalRequestController::class, 'link'])->name('customer-portal-requests.link');
    Route::post('/kundenanfragen/{customerPortalRequest}/dolibarr-kunde', [CustomerPortalRequestController::class, 'createCustomer'])->name('customer-portal-requests.create-customer');
    Route::patch('/kundenanfragen/{customerPortalRequest}/status', [CustomerPortalRequestController::class, 'updateStatus'])->name('customer-portal-requests.status');
    Route::get('/lagerverwaltung', [WarehouseController::class, 'index'])->name('warehouse.index');
    Route::get('/historie', [HistoryController::class, 'index'])->name('history.index');
    Route::resource('spare-part-categories', SparePartCategoryController::class)->only(['index', 'store', 'destroy']);
    Route::resource('spare-parts', SparePartController::class)->except(['show']);
    Route::post('/spare-parts/scan-stock', [SparePartController::class, 'scanStock'])->name('spare-parts.scan-stock');
    Route::resource('machine-documents', MachineDocumentController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('service-defaults', ServiceDefaultController::class)->only(['index', 'store', 'destroy']);

    Route::resource('portal-accounts', PortalAccountController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    Route::get('/intern/tickets', [InternesTicketController::class, 'index'])->name('interne-tickets.index');
    Route::patch('/intern/tickets/{internesTicket}/status', [InternesTicketController::class, 'updateStatus'])->name('interne-tickets.status');
});
