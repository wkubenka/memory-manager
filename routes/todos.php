<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('todos', 'pages::todos.index')->name('todos.index');
});
