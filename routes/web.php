<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'Auth\LoginController@showLoginForm');

Auth::routes();

/**
 * account
 */
Route::prefix('account')->group(function () {

    //dashboard account
    Route::get('/dashboard', 'account\DashboardController@index')->name('account.dashboard.index');

    //categories debit
    Route::get('/categories_debit/search', 'account\CategoriesDebitController@search')->name('account.categories_debit.search');
    Route::Resource('/categories_debit', 'account\CategoriesDebitController',['as' => 'account']);
    //debit
    Route::get('/debit/search', 'account\DebitController@search')->name('account.debit.search');
    Route::Resource('/debit', 'account\DebitController',['as' => 'account']);
    //categories credit
    Route::get('/categories_credit/search', 'account\CategoriesCreditController@search')->name('account.categories_credit.search');
    Route::Resource('/categories_credit', 'account\CategoriesCreditController',['as' => 'account']);
    //credit
    Route::get('/credit/search', 'account\CreditController@search')->name('account.credit.search');
    Route::Resource('/credit', 'account\CreditController',['as' => 'account']);
    //laporan debit
    Route::get('/laporan_debit', 'account\LaporanDebitController@index')->name('account.laporan_debit.index');
    Route::get('/laporan_debit/check', 'account\LaporanDebitController@check')->name('account.laporan_debit.check');
    Route::get('/laporan_debit/export', 'account\LaporanDebitController@export')->name('account.laporan_debit.export');
    //laporan credit
    Route::get('/laporan_credit', 'account\LaporanCreditController@index')->name('account.laporan_credit.index');
    Route::get('/laporan_credit/check', 'account\LaporanCreditController@check')->name('account.laporan_credit.check');
    Route::get('/laporan_credit/export', 'account\LaporanCreditController@export')->name('account.laporan_credit.export');

    // stock barang
    Route::get('/stock/search', 'account\StockBarangController@search')->name('account.stock_barang.search');
    Route::Resource('/stock', 'account\StockBarangController',['as' => 'account']);

    // master pempek
    Route::get('/master_pempek/search', 'account\MasterPempekController@search')->name('account.master_pempek.search');
    Route::Resource('/master_pempek', 'account\MasterPempekController', ['as' => 'account']);

    // produksi pempek (stock in)
    Route::get('/produksi/search', 'account\ProduksiPempekController@search')->name('account.produksi.search');
    Route::get('/produksi', 'account\ProduksiPempekController@index')->name('account.produksi.index');
    Route::get('/produksi/create', 'account\ProduksiPempekController@create')->name('account.produksi.create');
    Route::post('/produksi', 'account\ProduksiPempekController@store')->name('account.produksi.store');
    Route::get('/produksi/{no_faktur}', 'account\ProduksiPempekController@show')->name('account.produksi.show');

    // kasir penjualan (stock out)
    Route::get('/penjualan/search', 'account\PenjualanKasirController@search')->name('account.penjualan.search');
    Route::get('/penjualan', 'account\PenjualanKasirController@index')->name('account.penjualan.index');
    Route::get('/penjualan/create', 'account\PenjualanKasirController@create')->name('account.penjualan.create');
    Route::post('/penjualan', 'account\PenjualanKasirController@store')->name('account.penjualan.store');
    Route::get('/penjualan/{no_faktur}', 'account\PenjualanKasirController@show')->name('account.penjualan.show');
    Route::get('/penjualan/{no_faktur}/struk', 'account\PenjualanKasirController@struk')->name('account.penjualan.struk');

});
