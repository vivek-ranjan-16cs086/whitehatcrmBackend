<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\TargetController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\SettingController;

Route::get('privacy-policy', function () {
    return view('privacy-policy');
});

Route::middleware('guest')->group(function () {
    Route::get('login',    [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login',   [LoginController::class, 'login']);

    Route::get('password/reset',          [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('password/email',         [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('password/reset/{token}',  [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('password/reset',         [ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::get('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth','no-cache');

Route::get('/dashboard', fn() => redirect()->route('dashboard'));

Route::middleware(['auth','no-cache'])->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
	
	//Lead Management
	Route::prefix('lead-managment')->group(function () {
		//GET method 
		Route::GET('/', [LeadController::class, 'index'])->name('lead-management.index');
		Route::GET('ajax-list', [LeadController::class, 'ajaxList'])->name('lead-management.ajax-list');
		
		//POST METHOD 
		Route::post('update', [LeadController::class, 'updateDetails'])->name('lead-managment.update');
		Route::post('store', [LeadController::class, 'store'])->name('lead-managment.store');
	});
	 
	//project management
	
	Route::prefix('projects')->group(function () {
		Route::get('/', [ProjectController::class, 'index'])->name('admin.projects.index');
		Route::get('/ajax', [ProjectController::class, 'ajaxList'])->name('admin.projects.ajax');
		Route::post('/', [ProjectController::class, 'store'])->name('admin.projects.store');
		Route::get('/{project}/edit', [ProjectController::class, 'edit'])->name('admin.projects.edit');
		Route::put('/{project}', [ProjectController::class, 'update'])->name('admin.projects.update');
		Route::get('/delete/{id}', [ProjectController::class, 'destroy'])
        ->name('admin.projects.destroy');
	});
	
    //target management
    Route::prefix('targets')->group(function () {

		Route::get('/', [TargetController::class , 'index'])->name('admin.targets.index');
		Route::get('/ajax', [TargetController::class , 'ajaxList'])->name('admin.targets.ajaxList');
        Route::post('/', [TargetController::class , 'store'])->name('admin.targets.store');
        Route::get('/{target}/edit', [TargetController::class , 'edit'])->name('admin.targets.edit');
        Route::put('/{target}', [TargetController::class , 'update'])->name('admin.targets.update');
        Route::delete('/{target}', [TargetController::class , 'destroy'])->name('admin.targets.destroy');        
    });	
	
    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('admin.notifications.index');
        Route::get('/list', [NotificationController::class, 'getNotifications'])->name('admin.notifications.list');
        Route::patch('/read/{id}', [NotificationController::class, 'markAsRead'])->name('admin.notifications.read');
        Route::delete('/clear-all', [NotificationController::class, 'clearAllNotifications'])->name('admin.notifications.clearAll');
    });	

});


Route::prefix('admin')->name('admin.')->middleware(['auth', 'super-admin','no-cache'])->group(function () {
    Route::get('users/check-username', [UserController::class, 'checkUsername'])->name('users.checkUsername');
    Route::resource('users', Admin\UserController::class);
	Route::get('users/delete/{user}', [UserController::class, 'destroy']);
    Route::resource('roles', Admin\RoleController::class);
	Route::get('roles/delete/{role}', [RoleController::class, 'destroy']);
    Route::get('permissions', [Admin\PermissionController::class, 'index'])->name('permissions.index');
    Route::post('permissions/sync', [Admin\PermissionController::class, 'sync'])->name('permissions.sync');

    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('maintenance.update');
});

Route::get('/', function(){
	return view('welcome');  
});
	
