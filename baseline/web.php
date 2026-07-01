<?php

use Illuminate\Support\Facades\Route;

// routes/api.php
// Route::prefix('warehouse')->group(function () {
//     Route::get('/inventory', [WarehouseController::class, 'index']);
//     Route::post('/item', [WarehouseController::class, 'updateItem']);
//     Route::post('/move', [WarehouseController::class, 'moveItem']);
//     Route::get('/alerts', [WarehouseController::class, 'getLowStockAlerts']);
//     Route::get('/metrics', [WarehouseController::class, 'getDashboardMetrics']);
//     Route::get('/search', [WarehouseController::class, 'searchItems']);
// });

Route::get('/warehouse', function() {
    return view('warehouse');
});


use App\Http\Controllers\SampleReceptionController;

Route::get('/sample-reception', [SampleReceptionController::class, 'show'])->name('sample.form');
Route::post('/sample-reception', [SampleReceptionController::class, 'parse'])->name('sample.parse');



use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LifecycleController;
use App\Http\Controllers\LeaveAttendanceController;
use App\Http\Controllers\HiringController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\CompensationController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarketingCRMController;
use App\Http\Controllers\StatDashboardController;

// HRDashboard
Route::get('/hrdashboard', [DashboardController::class, 'index'])->name('dashboard');

// Employee Management
Route::prefix('employees')->name('employees.')->group(function () {
    Route::get('/', [EmployeeController::class, 'index'])->name('index');
    Route::get('/create', [EmployeeController::class, 'create'])->name('create');
    Route::post('/', [EmployeeController::class, 'store'])->name('store');
    Route::get('/{id}', [EmployeeController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [EmployeeController::class, 'edit'])->name('edit');
    Route::put('/{id}', [EmployeeController::class, 'update'])->name('update');
});

// Employee Lifecycle
Route::prefix('lifecycle')->name('lifecycle.')->group(function () {
    Route::get('/', [LifecycleController::class, 'index'])->name('index');
    Route::get('/onboarding', [LifecycleController::class, 'onboarding'])->name('onboarding');
    Route::get('/transitions', [LifecycleController::class, 'transitions'])->name('transitions');
    Route::get('/offboarding', [LifecycleController::class, 'offboarding'])->name('offboarding');
});

// Leave and Attendance
Route::prefix('attendance')->name('attendance.')->group(function () {
    Route::get('/', [LeaveAttendanceController::class, 'index'])->name('index');
    Route::get('/leave-requests', [LeaveAttendanceController::class, 'leaveRequests'])->name('leave');
    Route::get('/time-tracking', [LeaveAttendanceController::class, 'timeTracking'])->name('time');
    Route::post('/leave-request', [LeaveAttendanceController::class, 'submitLeave'])->name('submit-leave');
});

// Hiring
Route::prefix('hiring')->name('hiring.')->group(function () {
    Route::get('/', [HiringController::class, 'index'])->name('index');
    Route::get('/positions', [HiringController::class, 'positions'])->name('positions');
    Route::get('/candidates', [HiringController::class, 'candidates'])->name('candidates');
    Route::get('/interviews', [HiringController::class, 'interviews'])->name('interviews');
});

// Performance Management
Route::prefix('performance')->name('performance.')->group(function () {
    Route::get('/', [PerformanceController::class, 'index'])->name('index');
    Route::get('/reviews', [PerformanceController::class, 'reviews'])->name('reviews');
    Route::get('/goals', [PerformanceController::class, 'goals'])->name('goals');
    Route::get('/feedback', [PerformanceController::class, 'feedback'])->name('feedback');
});

// Compensation
Route::prefix('compensation')->name('compensation.')->group(function () {
    Route::get('/', [CompensationController::class, 'index'])->name('index');
    Route::get('/salary', [CompensationController::class, 'salary'])->name('salary');
    Route::get('/benefits', [CompensationController::class, 'benefits'])->name('benefits');
    Route::get('/payroll', [CompensationController::class, 'payroll'])->name('payroll');
});

// Analytics
Route::prefix('analytics')->name('analytics.')->group(function () {
    Route::get('/', [AnalyticsController::class, 'index'])->name('index');
    Route::get('/reports', [AnalyticsController::class, 'reports'])->name('reports');
    Route::get('/metrics', [AnalyticsController::class, 'metrics'])->name('metrics');
});

// Performance Management Active Routes
Route::prefix('performance')->name('performance.')->group(function () {
    Route::get('/', [PerformanceController::class, 'index'])->name('index');
    Route::get('/reviews', [PerformanceController::class, 'reviews'])->name('reviews');
    Route::post('/reviews/{id}/submit', [PerformanceController::class, 'submitReview'])->name('submit-review');
    Route::get('/goals', [PerformanceController::class, 'goals'])->name('goals');
    Route::post('/goals', [PerformanceController::class, 'createGoal'])->name('create-goal');
    Route::patch('/goals/{id}/progress', [PerformanceController::class, 'updateGoalProgress'])->name('update-goal');
    Route::get('/feedback', [PerformanceController::class, 'feedback'])->name('feedback');
    Route::post('/feedback', [PerformanceController::class, 'submitFeedback'])->name('submit-feedback');
});

// Analytics Active Routes
Route::prefix('analytics')->name('analytics.')->group(function () {
    Route::get('/', [AnalyticsController::class, 'index'])->name('index');
    Route::get('/reports', [AnalyticsController::class, 'reports'])->name('reports');
    Route::post('/reports/generate', [AnalyticsController::class, 'generateReport'])->name('generate-report');
    Route::get('/reports/{id}/download', [AnalyticsController::class, 'downloadReport'])->name('download-report');
    Route::get('/metrics', [AnalyticsController::class, 'metrics'])->name('metrics');
    Route::post('/metrics/export', [AnalyticsController::class, 'exportMetrics'])->name('export-metrics');
});

// Additional Active Routes for Employee Actions
Route::prefix('employees')->name('employees.')->group(function () {
    Route::post('/{id}/send-message', [EmployeeController::class, 'sendMessage'])->name('send-message');
    Route::patch('/{id}/deactivate', [EmployeeController::class, 'deactivate'])->name('deactivate');
    Route::post('/bulk-action', [EmployeeController::class, 'bulkAction'])->name('bulk-action');
    Route::get('/export', [EmployeeController::class, 'export'])->name('export');
});

use App\Http\Controllers\PdfReceptionController;

Route::get('/pdf-reception/upload', [PdfReceptionController::class, 'uploadForm'])->name('pdf.upload');
Route::post('/pdf-reception/parse', [PdfReceptionController::class, 'parsePdf'])->name('pdf.parse');
Route::post('/pdf-reception/confirm', [PdfReceptionController::class, 'confirmSelection'])->name('pdf.confirm');

use App\Http\Controllers\CrmController;
Route::prefix('crm')->name('crm.')->group(function () {
    // Lists
    Route::get('/',              [CrmController::class, 'index'])->name('index');
    Route::get('/prospects',     [CrmController::class, 'prospects'])->name('prospects');
    Route::get('/clients',       [CrmController::class, 'clients'])->name('clients');
    Route::get('/contacts',      [CrmController::class, 'contacts'])->name('contacts');
    Route::get('/opportunities', [CrmController::class, 'opportunities'])->name('opportunities');

    // Create/Edit (mock)
    Route::get('/create',        [CrmController::class, 'createAccount'])->name('create');
    Route::post('/',             [CrmController::class, 'storeAccount'])->name('store');
    Route::get('/{id}/edit',     [CrmController::class, 'editAccount'])->name('edit')->whereNumber('id');
    Route::post('/{id}',         [CrmController::class, 'updateAccount'])->name('update')->whereNumber('id');

    // Contacts (mock)
    Route::get('/{id}/contacts/create',     [CrmController::class, 'createContact'])->name('contacts.create')->whereNumber('id');
    Route::post('/{id}/contacts',           [CrmController::class, 'storeContact'])->name('contacts.store')->whereNumber('id');
    Route::get('/{id}/contacts/{idx}/edit', [CrmController::class, 'editContact'])->name('contacts.edit')->whereNumber('id')->whereNumber('idx');
    Route::post('/{id}/contacts/{idx}',     [CrmController::class, 'updateContact'])->name('contacts.update')->whereNumber('id')->whereNumber('idx');

    // Opportunities (mock)
    Route::get('/{id}/opportunities/create',        [CrmController::class, 'createOpportunity'])->name('opps.create')->whereNumber('id');
    Route::post('/{id}/opportunities',              [CrmController::class, 'storeOpportunity'])->name('opps.store')->whereNumber('id');
    Route::get('/{id}/opportunities/{idx}/edit',    [CrmController::class, 'editOpportunity'])->name('opps.edit')->whereNumber('id')->whereNumber('idx');
    Route::post('/{id}/opportunities/{idx}',        [CrmController::class, 'updateOpportunity'])->name('opps.update')->whereNumber('id')->whereNumber('idx');

    // DETAIL ROUTE MUST BE LAST + NUMERIC-ONLY
    Route::get('/{id}', [CrmController::class, 'show'])->name('show')->whereNumber('id');
});
// Notes & Attachments (mock)
Route::post('/{id}/notes', [CrmController::class, 'storeNote'])->name('notes.store')->whereNumber('id');
Route::post('/{id}/attachments', [CrmController::class, 'storeAttachment'])->name('attachments.store')->whereNumber('id');

// Marketing CRM Routes
Route::prefix('marketing-crm')->group(function () {
    Route::get('/', [MarketingCRMController::class, 'index'])->name('marketing-crm.index');
    
    // API endpoints
    Route::get('/api/clients', [MarketingCRMController::class, 'getClients']);
    Route::get('/api/clients/{id}', [MarketingCRMController::class, 'getClient']);
    Route::post('/api/clients', [MarketingCRMController::class, 'createClient']);
    Route::put('/api/clients/{id}', [MarketingCRMController::class, 'updateClient']);
    Route::delete('/api/clients/{id}', [MarketingCRMController::class, 'deleteClient']);
    
    Route::get('/api/opportunities', [MarketingCRMController::class, 'getOpportunities']);
    Route::post('/api/opportunities', [MarketingCRMController::class, 'createOpportunity']);
    
    Route::get('/api/dashboard', [MarketingCRMController::class, 'getDashboardStats']);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/stat-dashboards', [StatDashboardController::class, 'index'])->name('stat-dashboards.index');
    Route::get('/stat-dashboards/{section}', [StatDashboardController::class, 'section'])->name('stat-dashboards.section');
});
// In your routes/api.php or web.php
Route::get('/api/hr/employees', [EmployeeController::class, 'index']);
Route::delete('/api/hr/employees/{id}', [EmployeeController::class, 'destroy']);
Route::post('/api/hr/employees/bulk-delete', [EmployeeController::class, 'bulkDelete']);


use App\Http\Controllers\Inventory\SampleReceptionsController;

Route::get('/pdf', [SampleReceptionsController::class, 'show'])->name('sample.show');
Route::post('/parse', [SampleReceptionsController::class, 'parse'])->name('sample.parses');


Route::fallback(function(){
    return view('application');
});