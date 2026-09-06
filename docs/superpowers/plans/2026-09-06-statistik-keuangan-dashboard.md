# Statistik Keuangan Dalam 1 Tahun Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mengaktifkan grafik "STATISTIK KEUANGAN DALAM 1 TAHUN" pada halaman dashboard menggunakan Highcharts (tipe Column Chart) dengan perbandingan bulanan Uang Masuk vs Uang Keluar serta filter pilihan tahun.

**Architecture:** Pada `DashboardController@index`, menerima parameter tahun `year` (default tahun saat ini), mengagregasi total nominal debit dan credit per bulan (1 - 12) untuk tahun tersebut, dan mengirimkan array data 12 bulan beserta daftar tahun ke view. Di `resources/views/account/dashboard/index.blade.php`, menambahkan dropdown pilihan tahun di card header dan merender grafik Highcharts pada `<div id="container"></div>`.

**Tech Stack:** PHP 7.4 / Laravel 6.2, MySQL/MariaDB, Highcharts 4.0.4, Blade Templating, PHPUnit 8.5.

## Global Constraints
- Sumbu X menampilkan 12 bulan (Jan - Des).
- Tiap bulan menampilkan 2 batang: Uang Masuk (warna `#28a745`) dan Uang Keluar (warna `#dc3545`).
- Bulan tanpa transaksi harus bernilai `0`.
- Dropdown tahun memuat tahun transaksi yang ada serta tahun aktif saat ini.
- Tooltip menampilkan nominal dalam format Rupiah.

---

### Task 1: Agregasi Data Bulanan dan Filter Tahun di `DashboardController`

**Files:**
- Modify: `app/Http/Controllers/account/DashboardController.php`

**Interfaces:**
- Consumes: Request parameter `year` (opsional), data tabel `debit` dan `credit`.
- Produces: Variabel `$selected_year`, `$year_list`, `$chart_debit`, dan `$chart_credit` yang dikirim ke view `account.dashboard.index`.

- [x] **Step 1: Modifikasi `app/Http/Controllers/account/DashboardController.php`**

```php
<?php

namespace App\Http\Controllers\account;

use App\Debit;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * DashboardController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $userId = Auth::user()->id;
        $currentYear = Carbon::now()->year;
        $selected_year = (int) $request->get('year', $currentYear);

        $uang_masuk_bulan_ini = DB::table('debit')
            ->selectRaw('sum(nominal) as nominal')
            ->whereYear('debit_date', Carbon::now()->year)
            ->whereMonth('debit_date', Carbon::now()->month)
            ->where('user_id', $userId)
            ->first();

        $uang_keluar_bulan_ini = DB::table('credit')
            ->selectRaw('sum(nominal) as nominal')
            ->whereYear('credit_date', Carbon::now()->year)
            ->whereMonth('credit_date', Carbon::now()->month)
            ->where('user_id', $userId)
            ->first();

        $uang_masuk_bulan_lalu = DB::table('debit')
            ->selectRaw('sum(nominal) as nominal')
            ->whereYear('debit_date', Carbon::now()->year)
            ->whereMonth('debit_date', Carbon::now()->subMonths(1)->month)
            ->where('user_id', $userId)
            ->first();

        $uang_keluar_bulan_lalu = DB::table('credit')
            ->selectRaw('sum(nominal) as nominal')
            ->whereYear('credit_date', Carbon::now()->year)
            ->whereMonth('credit_date', Carbon::now()->subMonths(1)->month)
            ->where('user_id', $userId)
            ->first();

        $uang_masuk_selama_ini = DB::table('debit')
            ->selectRaw('sum(nominal) as nominal')
            ->where('user_id', $userId)
            ->first();

        $uang_keluar_selama_ini = DB::table('credit')
            ->selectRaw('sum(nominal) as nominal')
            ->where('user_id', $userId)
            ->first();

        // Saldo ringkasan
        $saldo_bulan_ini = ($uang_masuk_bulan_ini->nominal ?? 0) - ($uang_keluar_bulan_ini->nominal ?? 0);
        $saldo_bulan_lalu = ($uang_masuk_bulan_lalu->nominal ?? 0) - ($uang_keluar_bulan_lalu->nominal ?? 0);
        $saldo_selama_ini = ($uang_masuk_selama_ini->nominal ?? 0) - ($uang_keluar_selama_ini->nominal ?? 0);

        // Ambil daftar tahun dari transaksi debit & credit
        $debit_years = DB::table('debit')
            ->where('user_id', $userId)
            ->selectRaw('DISTINCT YEAR(debit_date) as year')
            ->pluck('year')
            ->toArray();

        $credit_years = DB::table('credit')
            ->where('user_id', $userId)
            ->selectRaw('DISTINCT YEAR(credit_date) as year')
            ->pluck('year')
            ->toArray();

        $year_list = array_values(array_unique(array_merge([$currentYear], $debit_years, $credit_years)));
        rsort($year_list);

        // Agregasi bulanan debit untuk tahun terpilih
        $debit_monthly = DB::table('debit')
            ->selectRaw('MONTH(debit_date) as month, sum(nominal) as total')
            ->where('user_id', $userId)
            ->whereYear('debit_date', $selected_year)
            ->groupBy(DB::raw('MONTH(debit_date)'))
            ->pluck('total', 'month')
            ->toArray();

        // Agregasi bulanan credit untuk tahun terpilih
        $credit_monthly = DB::table('credit')
            ->selectRaw('MONTH(credit_date) as month, sum(nominal) as total')
            ->where('user_id', $userId)
            ->whereYear('credit_date', $selected_year)
            ->groupBy(DB::raw('MONTH(credit_date)'))
            ->pluck('total', 'month')
            ->toArray();

        $chart_debit = [];
        $chart_credit = [];
        for ($m = 1; $m <= 12; $m++) {
            $chart_debit[] = (int) ($debit_monthly[$m] ?? 0);
            $chart_credit[] = (int) ($credit_monthly[$m] ?? 0);
        }

        return view('account.dashboard.index', compact(
            'saldo_selama_ini',
            'saldo_bulan_ini',
            'saldo_bulan_lalu',
            'selected_year',
            'year_list',
            'chart_debit',
            'chart_credit'
        ));
    }
}
```

- [x] **Step 2: Verifikasi sintaks PHP**

Run: `php -l app/Http/Controllers/account/DashboardController.php`
Expected: `No syntax errors detected`

- [x] **Step 3: Commit controller**

```bash
git add app/Http/Controllers/account/DashboardController.php
git commit -m "feat: tambahkan agregasi data bulanan dan filter tahun di DashboardController"
```

---

### Task 2: Implementasi Dropdown Filter & Highcharts pada View Dashboard

**Files:**
- Modify: `resources/views/account/dashboard/index.blade.php`

**Interfaces:**
- Consumes: `$selected_year`, `$year_list`, `$chart_debit`, `$chart_credit`.
- Produces: Tampilan visual grafik Column Chart Highcharts dan dropdown filter tahun.

- [x] **Step 1: Modifikasi `resources/views/account/dashboard/index.blade.php`**

Tambahkan dropdown tahun di card header dan inisialisasi Highcharts di script.

```html
@extends('layouts.account')

@section('title')
    Dashboard - {{ config('app.name') }}
@stop

@section('content')
    <div class="main-content">
        <section class="section">
            <div class="row">
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                    <div class="card card-statistic-2">
                        <div class="card-icon shadow-primary bg-primary">
                            <i class="fas fa-money-check-alt"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>SEMUA SALDO </h4>
                            </div>
                            <div class="card-body" style="font-size: 20px">
                                {{ rupiah($saldo_selama_ini) }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                    <div class="card card-statistic-2">
                        <div class="card-icon shadow-primary bg-primary">
                            <i class="fas fa-money-check-alt"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>SALDO BULAN INI</h4>
                            </div>
                            <div class="card-body" style="font-size: 20px">
                                {{ rupiah($saldo_bulan_ini) }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                    <div class="card card-statistic-2">
                        <div class="card-icon shadow-primary bg-primary">
                            <i class="fas fa-money-check-alt"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>SALDO BULAN LALU</h4>
                            </div>
                            <div class="card-body" style="font-size: 20px">
                                {{ rupiah($saldo_bulan_lalu) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-chart-pie"></i> STATISTIK KEUANGAN DALAM 1 TAHUN</h4>
                            <div class="card-header-action">
                                <form action="{{ route('account.dashboard.index') }}" method="GET" id="yearForm">
                                    <select name="year" class="form-control" onchange="document.getElementById('yearForm').submit()" style="font-weight: bold; border-radius: 20px; cursor: pointer;">
                                        @foreach($year_list as $year)
                                            <option value="{{ $year }}" {{ $selected_year == $year ? 'selected' : '' }}>
                                                Tahun {{ $year }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                        </div>

                        <div class="card-body">
                            <div id="container" style="min-height: 380px;"></div>
                        </div>
                    </div>
                </div>
            </div>

        </section>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Highcharts.chart('container', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: 'Statistik Pemasukan & Pengeluaran Tahun {{ $selected_year }}'
                },
                xAxis: {
                    categories: [
                        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                    ],
                    crosshair: true
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Nominal (Rp)'
                    },
                    labels: {
                        formatter: function () {
                            return 'Rp. ' + Highcharts.numberFormat(this.value, 0, ',', '.');
                        }
                    }
                },
                tooltip: {
                    headerFormat: '<span style="font-size:12px; font-weight:bold;">{point.key}</span><table>',
                    pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                        '<td style="padding:0; font-weight:bold;"><b>Rp. {point.y:,.0f}</b></td></tr>',
                    footerFormat: '</table>',
                    shared: true,
                    useHTML: true
                },
                plotOptions: {
                    column: {
                        pointPadding: 0.2,
                        borderWidth: 0,
                        borderRadius: 4
                    }
                },
                series: [{
                    name: 'Uang Masuk (Debit)',
                    color: '#28a745',
                    data: {!! json_encode($chart_debit) !!}
                }, {
                    name: 'Uang Keluar (Credit)',
                    color: '#dc3545',
                    data: {!! json_encode($chart_credit) !!}
                }],
                credits: {
                    enabled: false
                }
            });
        });
    </script>
@stop
```

- [x] **Step 2: Commit perubahan view**

```bash
git add resources/views/account/dashboard/index.blade.php
git commit -m "feat: tambahkan dropdown filter tahun dan visualisasi Highcharts pada view dashboard"
```

---

### Task 3: Automated Feature Test untuk Dashboard Chart

**Files:**
- Create: `tests/Feature/DashboardChartTest.php`

**Interfaces:**
- Consumes: Route `/account/dashboard`, tabel `debit` dan `credit`.
- Produces: Test assertion otomatis yang memverifikasi HTTP 200, variabel view chart, dan filter tahun.

- [x] **Step 1: Buat file test `tests/Feature/DashboardChartTest.php`**

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

class DashboardChartTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
    }

    public function test_dashboard_menampilkan_grafik_dan_data_bulanan()
    {
        $catDebit = CategoriesDebit::create(['user_id' => $this->user->id, 'name' => 'Penjualan']);
        $catCredit = CategoriesCredit::create(['user_id' => $this->user->id, 'name' => 'Operasional']);

        // Data tahun 2025 bulan 3 (Maret) dan bulan 7 (Juli)
        Debit::create([
            'user_id' => $this->user->id,
            'category_id' => $catDebit->id,
            'nominal' => 1500000,
            'description' => 'Pemasukan Maret 2025',
            'debit_date' => '2025-03-10 10:00:00'
        ]);

        Credit::create([
            'user_id' => $this->user->id,
            'category_id' => $catCredit->id,
            'nominal' => 500000,
            'description' => 'Pengeluaran Maret 2025',
            'credit_date' => '2025-03-15 11:00:00'
        ]);

        // Filter tahun 2025
        $response = $this->actingAs($this->user)->get(route('account.dashboard.index', ['year' => 2025]));

        $response->assertStatus(200);
        $response->assertViewHas('selected_year', 2025);
        $response->assertViewHas('year_list');
        $response->assertViewHas('chart_debit');
        $response->assertViewHas('chart_credit');

        $chartDebit = $response->viewData('chart_debit');
        $chartCredit = $response->viewData('chart_credit');

        // Bulan ke-3 (index 2): Maret
        $this->assertEquals(1500000, $chartDebit[2]);
        $this->assertEquals(500000, $chartCredit[2]);

        // Bulan lain bernilai 0
        $this->assertEquals(0, $chartDebit[0]); // Januari
    }
}
```

- [x] **Step 2: Jalankan test feature dashboard chart**

Run: `./vendor/bin/phpunit tests/Feature/DashboardChartTest.php`
Expected: 1 test, assertions passed (green).

- [x] **Step 3: Commit test file**

```bash
git add tests/Feature/DashboardChartTest.php
git commit -m "test: tambahkan automated feature test untuk dashboard financial chart"
```
