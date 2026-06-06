<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn() => response()->json(['service' => 'amartha-eticket-api', 'status' => 'ok']));
