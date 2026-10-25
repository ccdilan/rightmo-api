<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['name' => 'Rightmo API', 'version' => 'v1', 'health' => '/up', 'documentation' => '/docs/']))->name('home');
