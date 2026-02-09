<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('notes.index');
})->name('home');

if (app()->isLocal()) {
    Route::post('dev/login', function () {
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password'],
        );

        Auth::login($user);

        return redirect()->route('notes.index');
    })->name('dev.login');
}

require __DIR__.'/settings.php';
require __DIR__.'/notes.php';
require __DIR__.'/todos.php';
