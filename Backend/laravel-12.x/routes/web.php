<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

// Convenience redirect: many apps expect /dashboard — forward to Filament login
Route::get('/dashboard', function () {
    return redirect('/admin/login');
});