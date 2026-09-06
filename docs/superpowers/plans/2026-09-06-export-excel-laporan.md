# Export Excel Laporan Uang Masuk dan Uang Keluar Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menambahkan fitur export ke Excel (.xlsx) pada modul Laporan Uang Masuk (`/account/laporan_debit`) dan Laporan Uang Keluar (`/account/laporan_credit`) berdasarkan rentang tanggal yang dipilih menggunakan `maatwebsite/excel`.

**Architecture:** Menginstal package `maatwebsite/excel:^3.1`. Membuat dua kelas Export (`DebitExport` dan `CreditExport`) yang mengimplementasikan `FromView` dan `ShouldAutoSize`. Menggunakan view Blade khusus untuk mendefinisikan layout tabel Excel yang rapi (judul usaha, periode, nomor, kolom data, dan total). Menambahkan rute dan controller method `export()` di `LaporanDebitController` dan `LaporanCreditController`. Menambahkan tombol hijau "EXPORT EXCEL" di header tabel laporan.

**Tech Stack:** PHP 7.4 / Laravel 6.2, `maatwebsite/excel:^3.1`, PhpSpreadsheet, Blade Templating, PHPUnit 8.5.

## Global Constraints
- Menggunakan library `maatwebsite/excel:^3.1`.
- Seluruh data yang diekspor wajib disaring berdasarkan `user_id = Auth::id()`.
- Laporan uang masuk memuat informasi nama barang dan kuantitas jika ada transaksi terkait stok.
- Di bagian akhir tabel Excel wajib terdapat baris TOTAL nominal.
- Tombol export Excel tampil setelah pengguna memfilter tanggal laporan.

---

### Task 1: Instalasi Paket `maatwebsite/excel`

**Files:**
- Modify: `composer.json`
- Modify: `composer.lock`

**Interfaces:**
- Produces: Namespace `Maatwebsite\Excel\Facades\Excel` dan artisan provider siap digunakan.

- [x] **Step 1: Jalankan composer require `maatwebsite/excel:^3.1`**

```bash
composer require "maatwebsite/excel:^3.1"
```

- [x] **Step 2: Verifikasi instalasi paket**

Run: `php artisan package:discover`
Expected: `Discovered Package: maatwebsite/excel`

- [x] **Step 3: Commit instalasi paket**

```bash
git add composer.json composer.lock
git commit -m "build: install maatwebsite/excel v3.1 untuk export spreadsheet"
```

---

### Task 2: Pembuatan Export Class `DebitExport` dan `CreditExport`

**Files:**
- Create: `app/Exports/DebitExport.php`
- Create: `app/Exports/CreditExport.php`

**Interfaces:**
- Consumes: `$tanggal_awal`, `$tanggal_akhir`, data dari tabel `debit` dan `credit`.
- Produces: Objek export yang me-render view Excel dengan auto-sizing kolom.

- [x] **Step 1: Buat file `app/Exports/DebitExport.php`**

```php
<?php

namespace App\Exports;

use App\Debit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DebitExport implements FromView, ShouldAutoSize
{
    protected $tanggal_awal;
    protected $tanggal_akhir;

    public function __construct($tanggal_awal, $tanggal_akhir)
    {
        $this->tanggal_awal = $tanggal_awal;
        $this->tanggal_akhir = $tanggal_akhir;
    }

    public function view(): View
    {
        $debit = Debit::select(
            'debit.id',
            'debit.category_id',
            'debit.user_id',
            'debit.stock_id',
            'debit.qty',
            'debit.nominal',
            'debit.debit_date',
            'debit.description',
            'categories_debit.name as category_name',
            'stock_barang.nama_barang'
        )
            ->leftJoin('categories_debit', 'debit.category_id', '=', 'categories_debit.id')
            ->leftJoin('stock_barang', 'debit.stock_id', '=', 'stock_barang.id')
            ->where('debit.user_id', Auth::user()->id)
            ->whereDate('debit.debit_date', '>=', $this->tanggal_awal)
            ->whereDate('debit.debit_date', '<=', $this->tanggal_akhir)
            ->orderBy('debit.debit_date', 'ASC')
            ->get();

        $total_nominal = $debit->sum('nominal');

        return view('account.laporan_debit.excel', [
            'debit'         => $debit,
            'total_nominal' => $total_nominal,
            'tanggal_awal'  => $this->tanggal_awal,
            'tanggal_akhir' => $this->tanggal_akhir,
        ]);
    }
}
```

- [x] **Step 2: Buat file `app/Exports/CreditExport.php`**

```php
<?php

namespace App\Exports;

use App\Credit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CreditExport implements FromView, ShouldAutoSize
{
    protected $tanggal_awal;
    protected $tanggal_akhir;

    public function __construct($tanggal_awal, $tanggal_akhir)
    {
        $this->tanggal_awal = $tanggal_awal;
        $this->tanggal_akhir = $tanggal_akhir;
    }

    public function view(): View
    {
        $credit = Credit::select(
            'credit.id',
            'credit.category_id',
            'credit.user_id',
            'credit.nominal',
            'credit.credit_date',
            'credit.description',
            'categories_credit.name as category_name'
        )
            ->leftJoin('categories_credit', 'credit.category_id', '=', 'categories_credit.id')
            ->where('credit.user_id', Auth::user()->id)
            ->whereDate('credit.credit_date', '>=', $this->tanggal_awal)
            ->whereDate('credit.credit_date', '<=', $this->tanggal_akhir)
            ->orderBy('credit.credit_date', 'ASC')
            ->get();

        $total_nominal = $credit->sum('nominal');

        return view('account.laporan_credit.excel', [
            'credit'        => $credit,
            'total_nominal' => $total_nominal,
            'tanggal_awal'  => $this->tanggal_awal,
            'tanggal_akhir' => $this->tanggal_akhir,
        ]);
    }
}
```

- [x] **Step 3: Verifikasi sintaks PHP export classes**

Run: `php -l app/Exports/DebitExport.php && php -l app/Exports/CreditExport.php`
Expected: `No syntax errors detected`

- [x] **Step 4: Commit export classes**

```bash
git add app/Exports/
git commit -m "feat: tambahkan export class DebitExport dan CreditExport"
```

---

### Task 3: Pembuatan Template Blade Excel

**Files:**
- Create: `resources/views/account/laporan_debit/excel.blade.php`
- Create: `resources/views/account/laporan_credit/excel.blade.php`

**Interfaces:**
- Consumes: `$debit`/`$credit`, `$total_nominal`, `$tanggal_awal`, `$tanggal_akhir`.
- Produces: Struktur HTML table yang diubah PhpSpreadsheet menjadi spreadsheet `.xlsx` berformat rapi.

- [x] **Step 1: Buat `resources/views/account/laporan_debit/excel.blade.php`**

```html
<table>
    <thead>
        <tr>
            <th colspan="7" style="text-align: center; font-weight: bold; font-size: 16px;">LAPORAN UANG MASUK - {{ config('app.name') }}</th>
        </tr>
        <tr>
            <th colspan="7" style="text-align: center; font-size: 12px;">Periode: {{ $tanggal_awal }} s/d {{ $tanggal_akhir }}</th>
        </tr>
        <tr></tr>
        <tr style="background-color: #f2f2f2;">
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">NO.</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">TANGGAL</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">KATEGORI</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">BARANG TERJUAL</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">QTY</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">NOMINAL (Rp)</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">KETERANGAN</th>
        </tr>
    </thead>
    <tbody>
        @php $no = 1; @endphp
        @foreach($debit as $item)
            <tr>
                <td style="text-align: center; border: 1px solid #000000;">{{ $no++ }}</td>
                <td style="text-align: center; border: 1px solid #000000;">{{ $item->debit_date }}</td>
                <td style="border: 1px solid #000000;">{{ $item->category_name }}</td>
                <td style="border: 1px solid #000000;">{{ $item->nama_barang ?: '-' }}</td>
                <td style="text-align: center; border: 1px solid #000000;">{{ $item->qty ?: '-' }}</td>
                <td style="text-align: right; border: 1px solid #000000;">{{ $item->nominal }}</td>
                <td style="border: 1px solid #000000;">{{ $item->description }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th colspan="5" style="text-align: right; font-weight: bold; border: 1px solid #000000;">TOTAL</th>
            <th style="text-align: right; font-weight: bold; border: 1px solid #000000;">{{ $total_nominal }}</th>
            <th style="border: 1px solid #000000;"></th>
        </tr>
    </tfoot>
</table>
```

- [x] **Step 2: Buat `resources/views/account/laporan_credit/excel.blade.php`**

```html
<table>
    <thead>
        <tr>
            <th colspan="5" style="text-align: center; font-weight: bold; font-size: 16px;">LAPORAN UANG KELUAR - {{ config('app.name') }}</th>
        </tr>
        <tr>
            <th colspan="5" style="text-align: center; font-size: 12px;">Periode: {{ $tanggal_awal }} s/d {{ $tanggal_akhir }}</th>
        </tr>
        <tr></tr>
        <tr style="background-color: #f2f2f2;">
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">NO.</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">TANGGAL</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">KATEGORI</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">NOMINAL (Rp)</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">KETERANGAN</th>
        </tr>
    </thead>
    <tbody>
        @php $no = 1; @endphp
        @foreach($credit as $item)
            <tr>
                <td style="text-align: center; border: 1px solid #000000;">{{ $no++ }}</td>
                <td style="text-align: center; border: 1px solid #000000;">{{ $item->credit_date }}</td>
                <td style="border: 1px solid #000000;">{{ $item->category_name }}</td>
                <td style="text-align: right; border: 1px solid #000000;">{{ $item->nominal }}</td>
                <td style="border: 1px solid #000000;">{{ $item->description }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th colspan="3" style="text-align: right; font-weight: bold; border: 1px solid #000000;">TOTAL</th>
            <th style="text-align: right; font-weight: bold; border: 1px solid #000000;">{{ $total_nominal }}</th>
            <th style="border: 1px solid #000000;"></th>
        </tr>
    </tfoot>
</table>
```

- [x] **Step 3: Commit template Blade excel**

```bash
git add resources/views/account/laporan_debit/excel.blade.php resources/views/account/laporan_credit/excel.blade.php
git commit -m "feat: buat template blade excel untuk laporan debit dan credit"
```

---

### Task 4: Konfigurasi Routes dan Controller Method Export

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/account/LaporanDebitController.php`
- Modify: `app/Http/Controllers/account/LaporanCreditController.php`

**Interfaces:**
- Consumes: Request parameter `tanggal_awal`, `tanggal_akhir`.
- Produces: Response file download Excel `.xlsx`.

- [x] **Step 1: Tambahkan rute export di `routes/web.php`**

```php
    //laporan debit
    Route::get('/laporan_debit', 'account\LaporanDebitController@index')->name('account.laporan_debit.index');
    Route::get('/laporan_debit/check', 'account\LaporanDebitController@check')->name('account.laporan_debit.check');
    Route::get('/laporan_debit/export', 'account\LaporanDebitController@export')->name('account.laporan_debit.export');
    //laporan credit
    Route::get('/laporan_credit', 'account\LaporanCreditController@index')->name('account.laporan_credit.index');
    Route::get('/laporan_credit/check', 'account\LaporanCreditController@check')->name('account.laporan_credit.check');
    Route::get('/laporan_credit/export', 'account\LaporanCreditController@export')->name('account.laporan_credit.export');
```

- [x] **Step 2: Update `app/Http/Controllers/account/LaporanDebitController.php`**

Tambahkan filter `where('debit.user_id', Auth::user()->id)` pada `check()` dan method `export()`:

```php
<?php

namespace App\Http\Controllers\account;

use App\Debit;
use App\Exports\DebitExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class LaporanDebitController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('account.laporan_debit.index');
    }

    public function check(Request $request)
    {
        $this->validate($request, [
            'tanggal_awal'  => 'required',
            'tanggal_akhir' => 'required',
        ], [
            'tanggal_awal.required'  => 'Silahkan Pilih Tanggal Awal!',
            'tanggal_akhir.required' => 'Silahkan Pilih Tanggal Akhir!',
        ]);

        $tanggal_awal  = $request->input('tanggal_awal');
        $tanggal_akhir = $request->input('tanggal_akhir');

        $debit = Debit::select(
            'debit.id',
            'debit.category_id',
            'debit.user_id',
            'debit.stock_id',
            'debit.qty',
            'debit.nominal',
            'debit.debit_date',
            'debit.description',
            'categories_debit.id as id_category',
            'categories_debit.name',
            'stock_barang.nama_barang'
        )
            ->leftJoin('categories_debit', 'debit.category_id', '=', 'categories_debit.id')
            ->leftJoin('stock_barang', 'debit.stock_id', '=', 'stock_barang.id')
            ->where('debit.user_id', Auth::user()->id)
            ->whereDate('debit.debit_date', '>=', $tanggal_awal)
            ->whereDate('debit.debit_date', '<=', $tanggal_akhir)
            ->orderBy('debit.debit_date', 'ASC')
            ->paginate(10)
            ->appends(request()->except('page'));

        return view('account.laporan_debit.index', compact('debit', 'tanggal_awal', 'tanggal_akhir'));
    }

    public function export(Request $request)
    {
        $this->validate($request, [
            'tanggal_awal'  => 'required',
            'tanggal_akhir' => 'required',
        ]);

        $tanggal_awal  = $request->input('tanggal_awal');
        $tanggal_akhir = $request->input('tanggal_akhir');

        $filename = 'laporan-uang-masuk-' . $tanggal_awal . '-sd-' . $tanggal_akhir . '.xlsx';

        return Excel::download(new DebitExport($tanggal_awal, $tanggal_akhir), $filename);
    }
}
```

- [x] **Step 3: Update `app/Http/Controllers/account/LaporanCreditController.php`**

Tambahkan filter `where('credit.user_id', Auth::user()->id)` pada `check()` dan method `export()`:

```php
<?php

namespace App\Http\Controllers\account;

use App\Credit;
use App\Exports\CreditExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class LaporanCreditController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return view('account.laporan_credit.index');
    }

    public function check(Request $request)
    {
        $this->validate($request, [
            'tanggal_awal'  => 'required',
            'tanggal_akhir' => 'required',
        ], [
            'tanggal_awal.required'  => 'Silahkan Pilih Tanggal Awal!',
            'tanggal_akhir.required' => 'Silahkan Pilih Tanggal Akhir!',
        ]);

        $tanggal_awal  = $request->input('tanggal_awal');
        $tanggal_akhir = $request->input('tanggal_akhir');

        $credit = Credit::select(
            'credit.id',
            'credit.category_id',
            'credit.user_id',
            'credit.nominal',
            'credit.credit_date',
            'credit.description',
            'categories_credit.id as id_category',
            'categories_credit.name'
        )
            ->leftJoin('categories_credit', 'credit.category_id', '=', 'categories_credit.id')
            ->where('credit.user_id', Auth::user()->id)
            ->whereDate('credit.credit_date', '>=', $tanggal_awal)
            ->whereDate('credit.credit_date', '<=', $tanggal_akhir)
            ->orderBy('credit.credit_date', 'ASC')
            ->paginate(10)
            ->appends(request()->except('page'));

        return view('account.laporan_credit.index', compact('credit', 'tanggal_awal', 'tanggal_akhir'));
    }

    public function export(Request $request)
    {
        $this->validate($request, [
            'tanggal_awal'  => 'required',
            'tanggal_akhir' => 'required',
        ]);

        $tanggal_awal  = $request->input('tanggal_awal');
        $tanggal_akhir = $request->input('tanggal_akhir');

        $filename = 'laporan-uang-keluar-' . $tanggal_awal . '-sd-' . $tanggal_akhir . '.xlsx';

        return Excel::download(new CreditExport($tanggal_awal, $tanggal_akhir), $filename);
    }
}
```

- [x] **Step 4: Verifikasi sintaks PHP**

Run: `php -l routes/web.php && php -l app/Http/Controllers/account/LaporanDebitController.php && php -l app/Http/Controllers/account/LaporanCreditController.php`
Expected: `No syntax errors detected`

- [x] **Step 5: Commit controller dan routes**

```bash
git add routes/web.php app/Http/Controllers/account/LaporanDebitController.php app/Http/Controllers/account/LaporanCreditController.php
git commit -m "feat: tambahkan route dan method export excel di LaporanDebitController dan LaporanCreditController"
```

---

### Task 5: Penambahan Tombol "EXPORT EXCEL" di Halaman Laporan

**Files:**
- Modify: `resources/views/account/laporan_debit/index.blade.php`
- Modify: `resources/views/account/laporan_credit/index.blade.php`

**Interfaces:**
- Consumes: `$tanggal_awal`, `$tanggal_akhir`.
- Produces: Tombol aksi hijau yang mengarah ke route export dengan parameter tanggal aktif.

- [x] **Step 1: Update `resources/views/account/laporan_debit/index.blade.php`**

Pada card hasil filter (`@if (isset($debit))`), ubah card header menjadi:
```html
<div class="card-header">
    <h4><i class="fas fa-chart-line"></i> LAPORAN UANG MASUK</h4>
    <div class="card-header-action">
        <a href="{{ route('account.laporan_debit.export', ['tanggal_awal' => $tanggal_awal, 'tanggal_akhir' => $tanggal_akhir]) }}" class="btn btn-success">
            <i class="fa fa-file-excel"></i> EXPORT EXCEL
        </a>
    </div>
</div>
```
Dan tampilkan badge nama barang jika record debit memiliki `nama_barang`.

- [x] **Step 2: Update `resources/views/account/laporan_credit/index.blade.php`**

Pada card hasil filter (`@if (isset($credit))`), tambahkan tombol yang sama di card header:
```html
<div class="card-header">
    <h4><i class="fas fa-chart-line"></i> LAPORAN UANG KELUAR</h4>
    <div class="card-header-action">
        <a href="{{ route('account.laporan_credit.export', ['tanggal_awal' => $tanggal_awal, 'tanggal_akhir' => $tanggal_akhir]) }}" class="btn btn-success">
            <i class="fa fa-file-excel"></i> EXPORT EXCEL
        </a>
    </div>
</div>
```

- [x] **Step 3: Commit perubahan views**

```bash
git add resources/views/account/laporan_debit/index.blade.php resources/views/account/laporan_credit/index.blade.php
git commit -m "feat: tambahkan tombol export excel di view laporan debit dan credit"
```

---

### Task 6: Automated Feature Test untuk Export Excel

**Files:**
- Create: `tests/Feature/LaporanExportTest.php`

**Interfaces:**
- Consumes: Route `account.laporan_debit.export`, `account.laporan_credit.export`.
- Produces: Test assertion otomatis untuk verifikasi response binary download `.xlsx`.

- [x] **Step 1: Buat `tests/Feature/LaporanExportTest.php`**

```php
<?php

namespace Tests\Feature;

use App\CategoriesCredit;
use App\CategoriesDebit;
use App\Credit;
use App\Debit;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LaporanExportTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
    }

    public function test_export_laporan_debit_menghasilkan_file_excel()
    {
        $category = CategoriesDebit::create(['user_id' => $this->user->id, 'name' => 'Penjualan']);
        Debit::create([
            'user_id'     => $this->user->id,
            'category_id' => $category->id,
            'nominal'     => 100000,
            'description' => 'Test Debit Export',
            'debit_date'  => '2026-03-01 10:00:00'
        ]);

        $response = $this->actingAs($this->user)->get(route('account.laporan_debit.export', [
            'tanggal_awal'  => '2026-03-01',
            'tanggal_akhir' => '2026-03-31'
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
        $this->assertTrue(
            strpos($response->headers->get('content-disposition'), 'laporan-uang-masuk-2026-03-01-sd-2026-03-31.xlsx') !== false
        );
    }

    public function test_export_laporan_credit_menghasilkan_file_excel()
    {
        $category = CategoriesCredit::create(['user_id' => $this->user->id, 'name' => 'Operasional']);
        Credit::create([
            'user_id'     => $this->user->id,
            'category_id' => $category->id,
            'nominal'     => 50000,
            'description' => 'Test Credit Export',
            'credit_date' => '2026-03-01 10:00:00'
        ]);

        $response = $this->actingAs($this->user)->get(route('account.laporan_credit.export', [
            'tanggal_awal'  => '2026-03-01',
            'tanggal_akhir' => '2026-03-31'
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
        $this->assertTrue(
            strpos($response->headers->get('content-disposition'), 'laporan-uang-keluar-2026-03-01-sd-2026-03-31.xlsx') !== false
        );
    }
}
```

- [x] **Step 2: Jalankan test suite export laporan**

Run: `./vendor/bin/phpunit tests/Feature/LaporanExportTest.php`
Expected: 2 tests, assertions passed (green).

- [x] **Step 3: Jalankan seluruh test suite**

Run: `./vendor/bin/phpunit`
Expected: All tests pass.

- [x] **Step 4: Commit test suite**

```bash
git add tests/Feature/LaporanExportTest.php
git commit -m "test: tambahkan automated feature test untuk export excel laporan debit dan credit"
```
