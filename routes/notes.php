<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('notes', 'pages::notes.index')->name('notes.index');
});
