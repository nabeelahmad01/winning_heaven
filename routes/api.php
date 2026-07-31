<?php

use App\Http\Controllers\Api\AccountRequestController;
use App\Http\Controllers\Api\AdminStatsController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CoinsController;
use App\Http\Controllers\Api\DeletedPlayerController;
use App\Http\Controllers\Api\DistributorController;
use App\Http\Controllers\Api\FreeplayController;
use App\Http\Controllers\Api\GameAccountController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\GatewayController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\PushController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\ShiftReportController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserAdminController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::get('/register', [AuthController::class, 'checkEmail']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth');
    Route::get('/session-status', [AuthController::class, 'sessionStatus'])->middleware('auth');
    Route::post('/google', [\App\Http\Controllers\Api\GoogleAuthController::class, 'login']);
    Route::get('/google/ticket', [\App\Http\Controllers\Api\GoogleAuthController::class, 'redeemTicket']);
    Route::post('/google/ticket', [\App\Http\Controllers\Api\GoogleAuthController::class, 'redeemTicket']);
});

Route::get('/settings/frontend', [SettingsController::class, 'frontend']);
Route::get('/settings/global', [SettingsController::class, 'global']);
Route::get('/games', [GameController::class, 'index']);
Route::get('/gateways', [GatewayController::class, 'index']);
Route::get('/promotions', [PromotionController::class, 'index']);

Route::post('/distributors/login', [DistributorController::class, 'login']);
Route::post('/agents/login', [AgentController::class, 'login']);
Route::post('/agents', [AgentController::class, 'store']);

Route::middleware('auth')->group(function () {
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::patch('/transactions/{publicId}', [TransactionController::class, 'update']);

    Route::get('/coins-notifications', [CoinsController::class, 'index']);
    Route::patch('/coins-notifications/{publicId}', [CoinsController::class, 'update']);

    Route::get('/account-requests', [AccountRequestController::class, 'index']);
    Route::post('/account-requests', [AccountRequestController::class, 'store']);
    Route::patch('/account-requests/{publicId}', [AccountRequestController::class, 'update']);

    Route::get('/game-accounts', [GameAccountController::class, 'index']);
    Route::put('/game-accounts', [GameAccountController::class, 'update']);
    Route::delete('/game-accounts', [GameAccountController::class, 'destroy']);

    Route::get('/support', [SupportController::class, 'index']);
    Route::post('/support', [SupportController::class, 'store']);

    Route::get('/freeplay/gate', [FreeplayController::class, 'gate']);
    Route::post('/freeplay/claim', [FreeplayController::class, 'claim']);

    Route::get('/referrals/pending', [ReferralController::class, 'index']);
    Route::post('/referrals/pending/claim', [ReferralController::class, 'claim']);

    Route::post('/promotions', [PromotionController::class, 'store']);
    Route::delete('/promotions/{publicId}', [PromotionController::class, 'destroy']);
    Route::post('/promotions/claim', [PromotionController::class, 'claim']);

    Route::get('/admin/shift-reports', [ShiftReportController::class, 'index']);
    Route::post('/admin/shift-reports', [ShiftReportController::class, 'store']);
    Route::get('/admin/deleted-players', [DeletedPlayerController::class, 'index']);
    Route::post('/admin/deleted-players/{email}/restore', [DeletedPlayerController::class, 'restore']);
    Route::get('/admin/stats', [AdminStatsController::class, 'index']);
    Route::get('/admin/stats/by-date', [AdminStatsController::class, 'byDate']);

    Route::post('/push/subscribe', [PushController::class, 'subscribe']);
    Route::post('/push/broadcast', [PushController::class, 'broadcast']);
    Route::post('/users/subscribe', [UserAdminController::class, 'subscribe']);

    Route::post('/games', [GameController::class, 'store']);
    Route::patch('/games/{publicId}', [GameController::class, 'update']);
    Route::delete('/games/{publicId}', [GameController::class, 'destroy']);
    Route::post('/gateways', [GatewayController::class, 'store']);
    Route::patch('/gateways/{publicId}', [GatewayController::class, 'update']);
    Route::delete('/gateways/{publicId}', [GatewayController::class, 'destroy']);

    Route::put('/settings/global', [SettingsController::class, 'updateGlobal']);
    Route::put('/settings/frontend', [SettingsController::class, 'updateFrontend']);

    Route::get('/users', [UserAdminController::class, 'index']);
    Route::post('/users', [UserAdminController::class, 'store']);
    Route::patch('/users/{id}', [UserAdminController::class, 'update']);
    Route::delete('/users/{id}', [UserAdminController::class, 'destroy']);

    Route::get('/distributors', [DistributorController::class, 'index']);
    Route::post('/distributors', [DistributorController::class, 'store']);
    Route::patch('/distributors/{publicId}', [DistributorController::class, 'update']);
    Route::delete('/distributors/{publicId}', [DistributorController::class, 'destroy']);
    Route::get('/distributors/{publicId}/stats', [DistributorController::class, 'stats']);
    Route::get('/distributors/{publicId}/stats/by-date', [DistributorController::class, 'statsByDate']);
    Route::get('/distributors/{publicId}/players', [DistributorController::class, 'players']);
    Route::post('/distributors/{publicId}/players', [DistributorController::class, 'createPlayer']);
    Route::post('/distributors/{publicId}/cashout', [DistributorController::class, 'cashout']);
    Route::post('/distributors/{publicId}/website-pay', [DistributorController::class, 'websitePay']);
    Route::get('/distributors/{publicId}/gateways', [DistributorController::class, 'gateways']);
    Route::post('/distributors/{publicId}/gateways', [DistributorController::class, 'storeGateway']);
    Route::get('/distributors/{publicId}/staff', [DistributorController::class, 'staff']);
    Route::post('/distributors/{publicId}/staff', [DistributorController::class, 'storeStaff']);

    Route::get('/agents', [AgentController::class, 'index']);
    Route::patch('/agents/{id}', [AgentController::class, 'update']);
    Route::delete('/agents/{id}', [AgentController::class, 'destroy']);
    Route::get('/agents/{code}/stats', [AgentController::class, 'stats']);
    Route::get('/agents/{code}/signup-report', [AgentController::class, 'signupReport']);
    Route::get('/agents/{code}/daily-transactions', [AgentController::class, 'dailyTransactions']);
    Route::post('/agents/{code}/cashout', [AgentController::class, 'cashout']);
    Route::get('/campaign-requests', [AgentController::class, 'campaigns']);
    Route::post('/campaign-requests', [AgentController::class, 'storeCampaign']);
    Route::patch('/campaign-requests/{publicId}', [AgentController::class, 'updateCampaign']);
});
