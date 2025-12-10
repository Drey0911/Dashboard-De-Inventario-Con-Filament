<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\EntradaController;
use App\Http\Controllers\SalidaController;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::resource('proveedores', ProveedorController::class);
    Route::resource('categorias', CategoriaController::class);
    Route::resource('clientes', ClienteController::class);
    Route::resource('productos', ProductoController::class);
    Route::resource('entradas', EntradaController::class);
    Route::resource('salidas', SalidaController::class);
    

    // Rutas para proveedores
    Route::prefix('proveedores')->group(function () {
        Route::get('/export/excel', [ProveedorController::class, 'exportExcel'])
            ->name('proveedores.export.excel');
        Route::get('/export/pdf', [ProveedorController::class, 'exportPdf'])
            ->name('proveedores.export.pdf');
    });

    // Rutas para Categorias
    Route::prefix('categorias')->group(function () {
        Route::get('/export/excel', [CategoriaController::class, 'exportExcel'])
            ->name('categorias.export.excel');
        Route::get('/export/pdf', [CategoriaController::class, 'exportPdf'])
            ->name('categorias.export.pdf');
    });

    // Rutas para Clientes
    Route::prefix('clientes')->group(function () {
        Route::get('/export/excel', [ClienteController::class, 'exportExcel'])
            ->name('clientes.export.excel');
        Route::get('/export/pdf', [ClienteController::class, 'exportPdf'])
            ->name('clientes.export.pdf');
    });

    // Rutas para Productos
    Route::prefix('productos')->group(function () {
        Route::get('/export/excel', [ProductoController::class, 'exportExcel'])
            ->name('productos.export.excel');
        Route::get('/export/pdf', [ProductoController::class, 'exportPdf'])
            ->name('productos.export.pdf');
    });

    // Rutas para Entradas
    Route::prefix('entradas')->group(function () {
        Route::get('/export/excel', [EntradaController::class, 'exportExcel'])
            ->name('entradas.export.excel');
        Route::get('/export/pdf', [EntradaController::class, 'exportPdf'])
            ->name('entradas.export.pdf');
    });

    // Rutas para Salidas
    Route::prefix('salidas')->group(function () {
        Route::get('/export/excel', [SalidaController::class, 'exportExcel'])
            ->name('salidas.export.excel');     
        Route::get('/export/pdf', [SalidaController::class, 'exportPdf'])
            ->name('salidas.export.pdf');
    });

});