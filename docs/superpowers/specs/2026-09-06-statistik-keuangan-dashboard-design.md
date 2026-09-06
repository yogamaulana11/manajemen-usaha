# Desain Spesifikasi: Statistik Keuangan Dalam 1 Tahun di Halaman Dashboard

## 1. Ringkasan
Fitur ini mengaktifkan dan melengkapi card "STATISTIK KEUANGAN DALAM 1 TAHUN" yang sebelumnya masih kosong di halaman dashboard (`account.dashboard.index`). Grafik menggunakan Highcharts (tipe Column Chart) untuk memvisualisasikan perbandingan bulanan antara total Uang Masuk (`Debit`) dan Uang Keluar (`Credit`) dari bulan Januari hingga Desember pada tahun yang dipilih pengguna.

## 2. Kebutuhan & Perilaku Sistem
1. **Visualisasi Data**:
   - Menampilkan diagram batang (Column Chart) berdampingan per bulan (Jan - Des).
   - Terdapat 2 series:
     - **Uang Masuk**: Mengagregasikan total nominal debit pada bulan tersebut (Warna Hijau `#28a745`).
     - **Uang Keluar**: Mengagregasikan total nominal credit pada bulan tersebut (Warna Merah `#dc3545`).
   - Sumbu X menampilkan nama-nama bulan (Jan, Feb, Mar, Apr, Mei, Jun, Jul, Agu, Sep, Okt, Nov, Des).
   - Tooltip menampilkan format mata uang Rupiah (`Rp. X.XXX.XXX`).
2. **Filter Tahun**:
   - Di sisi kanan card header, terdapat dropdown pilihan tahun.
   - Pilihan tahun memuat tahun-tahun transaksi yang pernah dicatat oleh pengguna (debit & credit) serta tahun berjalan saat ini.
   - Mengubah pilihan dropdown tahun secara otomatis me-reload halaman dengan data tahun terpilih via GET request (`?year=YYYY`).
   - Default tahun adalah tahun berjalan (`Carbon::now()->year`).
3. **Penanganan Data Kosong**:
   - Jika pada bulan tertentu tidak ada transaksi, nilai data bulanan diisi `0` agar grafik tetap utuh 12 bulan tanpa error.

## 3. Desain Controller (`App\Http\Controllers\account\DashboardController`)
- Method `index(Request $request)`:
  1. Ambil `$selected_year = $request->get('year', Carbon::now()->year)`.
  2. Ambil daftar tahun:
     - Ambil `YEAR(debit_date)` dari tabel `debit` (milik user).
     - Ambil `YEAR(credit_date)` dari tabel `credit` (milik user).
     - Gabungkan, unikkan, sertakan `Carbon::now()->year`, urutkan menurun (`rsort`).
  3. Query agregasi bulanan untuk tahun terpilih:
     - `debit`: `selectRaw('MONTH(debit_date) as month, sum(nominal) as total')->where('user_id', Auth::id())->whereYear('debit_date', $selected_year)->groupBy(DB::raw('MONTH(debit_date)'))->pluck('total', 'month')`.
     - `credit`: `selectRaw('MONTH(credit_date) as month, sum(nominal) as total')->where('user_id', Auth::id())->whereYear('credit_date', $selected_year)->groupBy(DB::raw('MONTH(credit_date)'))->pluck('total', 'month')`.
  4. Bentuk array `$chart_debit` dan `$chart_credit` untuk 12 elemen (bulan 1 s/d 12):
     ```php
     $chart_debit = [];
     $chart_credit = [];
     for ($m = 1; $m <= 12; $m++) {
         $chart_debit[] = (int) ($debit_monthly[$m] ?? 0);
         $chart_credit[] = (int) ($credit_monthly[$m] ?? 0);
     }
     ```
  5. Teruskan data ke view `account.dashboard.index`.

## 4. Desain Tampilan Blade (`resources/views/account/dashboard/index.blade.php`)
1. **Card Header**:
   - Tambahkan `<div class="card-header-action">` yang berisi `<select name="year" onchange="this.form.submit()">` di dalam form GET.
2. **Highcharts Script**:
   - Panggil `Highcharts.chart('container', { ... })` di dalam block `<script>`:
     - `chart: { type: 'column' }`
     - `title: { text: 'Statistik Keuangan Tahun ' + selectedYear }`
     - `xAxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'], crosshair: true }`
     - `yAxis: { min: 0, title: { text: 'Nominal (Rp.)' } }`
     - `tooltip: { ... }`
     - `series: [{ name: 'Uang Masuk', color: '#28a745', data: [...] }, { name: 'Uang Keluar', color: '#dc3545', data: [...] }]`

## 5. Rencana Pengujian
- Unit / Feature test di `tests/Feature/DashboardChartTest.php`:
  - Menguji akses route `/account/dashboard` berhasil (HTTP 200).
  - Menguji view menerima variabel `$chart_debit`, `$chart_credit`, `$selected_year`, dan `$year_list`.
  - Menguji filter `?year=XXXX` mengagregasi data transaksi dengan tepat sesuai tahun yang dipilih.
