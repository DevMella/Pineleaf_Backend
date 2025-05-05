<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

Route::get('/', function () {
    return view('documentation');
});

Route::get('/property', function () {
    $markdown = File::get(resource_path('docs/property.md'));
    $html = Str::markdown($markdown); // Laravel 9+ has Str::markdown()

    return view('api', ['html' => $html]);
});
