<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TargetController;
use App\Http\Controllers\Api\EventController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ResponseController;


Route::prefix('v1')->group(function () { 

    Route::post('/sign-in', [AuthController::class, 'signIn']);
    Route::post('/sign-up', [AuthController::class, 'signUp']);
    Route::post('/forgot-password',[AuthController::class,'forgotPassword']);
    Route::post('/verify-otp',[AuthController::class,'verifyOtp']);
    Route::post('/reset-password',[AuthController::class,'resetPassword']);
    Route::post('/check-email', [AuthController::class, 'checkEmail']);	

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/user', [AuthController::class, 'user']);
		Route::post('/update-profile', [AuthController::class, 'updateProfile']);
		Route::put('/users/{id}/change-status', [AuthController::class, 'changeStatus']);
        
        Route::post('/sidebar', [ApiController::class, 'getSidebar']);

		Route::post('/store-device-token', [AuthController::class, 'storeDeviceId']);
		Route::get('/get-leads', [LeadController::class, 'getLeads']);
		Route::post('/leads/update-lead-status/{id}', [LeadController::class, 'updateLeadStatus']);
		Route::get('/leads/{id}', [LeadController::class, 'getSingleLead']);
		Route::post('/create-lead', [LeadController::class, 'createNewLead']);
		Route::post('/shuffle-leads', [LeadController::class, 'shuffleLeads']);
		Route::get('/followup-leads', [LeadController::class, 'getFollowUp']);
		Route::post('heartbeat', [ApiController::class, 'heartbeat']);  
		
		
		//routes for projects
		Route::prefix('projects')->group(function () {
			Route::get('/', [ProjectController::class, 'getProjects']);
			Route::post('/', [ProjectController::class, 'storeProject']);
			Route::get('{id}', [ProjectController::class, 'showProject']);
			Route::patch('{id}', [ProjectController::class, 'updateProject']);
			Route::delete('{id}', [ProjectController::class, 'destroyProject']);
			Route::post('{id}/change-status', [ProjectController::class, 'changeStatus']);
		});
		
		//route for notifications
		Route::prefix('notifications')->group(function () {

			Route::get('/', [ApiController::class, 'getNotifications']);
			Route::patch('/read/{id}', [ApiController::class, 'markAsRead']);
			Route::delete('/{id}', [ApiController::class, 'deleteNotification']);
			Route::delete('/', [ApiController::class, 'clearAllNotifications']);
			Route::get('/count', [ApiController::class, 'notificationCount']);
		}); 
		
		//routes for targets
		Route::prefix('targets')->group(function () {
			Route::get('/', [TargetController::class, 'getTargets']);
			Route::post('/', [TargetController::class, 'createTarget']);
			Route::get('/{id}', [TargetController::class, 'show']);
			Route::post('/{id}', [TargetController::class, 'update']);
			Route::delete('/{id}', [TargetController::class, 'destroy']);
		});
		
		Route::get('/weekly-visit-target', [LeadController::class, 'weeklyVisitTarget']);
		Route::get('/weekly-booking-target', [LeadController::class, 'weeklyBookingTarget']);
		Route::get('/performance-ranking', [LeadController::class, 'performanceRanking']);
		//Route::get('/followup-ranking', [LeadController::class, 'followUpRanking']);
		Route::get('/pipeline-health', [LeadController::class, 'pipelineHealth']);
		Route::get('/counts', [LeadController::class, 'dashboardCards']);
		Route::get('/timeline/{leadId}', [EventController::class, 'getTimeline']);
		
		Route::prefix('leads/{leadId}')->group(function () {

			// Comments
			Route::get('/comments', [EventController::class, 'getComments']);
			Route::post('/comments', [EventController::class, 'addComment']);

			// Follow-ups
			Route::get('/follow-ups', [EventController::class, 'getFollowUps']);

			// Calls
			Route::get('/calls', [EventController::class, 'getCalls']);
			Route::post('/calls', [EventController::class, 'addCall']);

			// Assignment
			Route::post('/assign', [EventController::class, 'assignLead']);
		});
		
		Route::get('/responses', [ResponseController::class, 'getResponses']); 

        Route::get('/sub-responses/{responseId}', [ResponseController::class, 'getSubResponses']);
		Route::post('/leads/import', [ResponseController::class, 'import']);
    }); 
    
	Route::prefix('leads')->group( function () {
		Route::GET('/', [LeadController::class, 'getLeads']);
		Route::POST('store', [LeadController::class, 'storeLead']);
		
	});
	
	Route::middleware(['auth:api', 'super-admin'])->group(function () {
        
        Route::post('/users', [ApiController::class, 'createUser']);
        Route::get('/users/{id}', [ApiController::class, 'getUser']);
        Route::post('/users/{id}', [ApiController::class, 'updateUser']);
        Route::delete('/users/{id}', [ApiController::class, 'deleteUser']);
		Route::get('/users', [ApiController::class, 'getUsers']); 
		Route::get('/all-users', [ApiController::class, 'getAllUsers']);
		Route::post('/assign-lead', [LeadController::class, 'assignLead']);
    });
	
	Route::get('/roles', [ApiController::class, 'getRoles']);
	Route::get('/status-list', [LeadController::class, 'getStatusList']); 
	Route::get('/lead-source', [LeadController::class, 'getLeadSourceList']);
	Route::get('/deactivate-list', [LeadController::class, 'getDeactivateList']);
	Route::get('/trigger-reminder', [LeadController::class, 'triggerReminder']);
	Route::get('/inventory-list', [ProjectController::class, 'getInventoryList']);
	Route::get('/visit-status-options', [LeadController::class, 'getVisitStatusOptions']);
	
	Route::get('/maintenance-mode', [SettingController::class, 'maintenanceMode']);
});
