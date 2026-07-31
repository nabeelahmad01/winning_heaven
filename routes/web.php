<?php

use App\Http\Controllers\Web\PortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PortalController::class, 'home'])->name('home');
Route::get('/login', [PortalController::class, 'login'])->name('login');
Route::get('/register', [PortalController::class, 'register'])->name('register');
Route::get('/info', [PortalController::class, 'info'])->name('info');

Route::get('/admin/login', [PortalController::class, 'adminLogin'])->name('admin.login');

Route::middleware('auth')->group(function () {
    Route::get('/lobby', [PortalController::class, 'lobby'])->name('lobby');
    Route::get('/referrals', [PortalController::class, 'referrals'])->name('referrals');

    // Deep-linkable HQ / staff portal tabs (Jackpot parity: /admin/{tab})
    Route::get('/admin/{tab?}', [PortalController::class, 'admin'])
        ->where('tab', '[a-z0-9_]+')->name('admin');
    Route::get('/finance/{tab?}', [PortalController::class, 'admin'])
        ->where('tab', '[a-z0-9_]+')->name('finance');
    Route::get('/operations/{tab?}', [PortalController::class, 'admin'])
        ->where('tab', '[a-z0-9_]+')->name('operations');
    Route::get('/coins-staff/{tab?}', [PortalController::class, 'admin'])
        ->where('tab', '[a-z0-9_]+')->name('coins-staff');
    Route::get('/support-staff/{tab?}', [PortalController::class, 'admin'])
        ->where('tab', '[a-z0-9_]+')->name('support-staff');
    Route::get('/boss/{tab?}', [PortalController::class, 'admin'])
        ->where('tab', '[a-z0-9_]+')->name('boss');
});

Route::get('/distributor/{tab?}', [PortalController::class, 'distributor'])
    ->where('tab', '[a-z0-9_]+')->name('distributor');

// Affiliate: team/create before catch-all tab
Route::get('/affiliate/team/create', [PortalController::class, 'affiliate'])->name('affiliate.team.create');
Route::get('/affiliate/{tab?}', [PortalController::class, 'affiliate'])
    ->where('tab', '[a-z0-9_]+')->name('affiliate');

Route::get('/clear-cache', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('config:cache');
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        
        $mailer = config('mail.default');
        $smtpUser = config('mail.mailers.smtp.username');
        $smtpPass = config('mail.mailers.smtp.password');
        $googleId = config('services.google.client_id');
        $hasSmtpCreds = filled($smtpUser) && filled($smtpPass) ? 'YES' : 'NO';

        // Read last 25 lines of laravel.log
        $logPath = storage_path('logs/laravel.log');
        $logLines = [];
        if (file_exists($logPath)) {
            $file = file($logPath);
            $logLines = array_slice($file, -25);
        }
        $logOutput = implode("", $logLines);

        return "Cache cleared successfully!<br><br><b>Debug Configs:</b><br>" .
               "MAIL_MAILER: " . htmlspecialchars($mailer) . "<br>" .
               "SMTP USERNAME: " . htmlspecialchars($smtpUser) . "<br>" .
               "SMTP PASSWORD SET: " . (filled($smtpPass) ? 'YES (Length: '.strlen($smtpPass).')' : 'NO') . "<br>" .
               "HAS SMTP CREDS: " . $hasSmtpCreds . "<br>" .
               "GOOGLE CLIENT ID: " . htmlspecialchars($googleId) . "<br><br>" .
               "<b>Last 25 Log Lines:</b><br><pre>" . htmlspecialchars($logOutput) . "</pre>";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

Route::get('/test-mail', function () {
    try {
        \Illuminate\Support\Facades\Mail::raw('This is a test email from Winning Heaven.', function ($message) {
            $message->to('verified@winningheaven.com')->subject('Winning Heaven Test Email');
        });
        return "Test email sent successfully!";
    } catch (\Exception $e) {
        return "Mail Send Error: " . $e->getMessage() . "<br><br>File: " . $e->getFile() . ":" . $e->getLine();
    }
});
