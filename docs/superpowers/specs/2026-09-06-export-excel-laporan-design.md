# Desain Spesifikasi: Fitur Export ke Excel pada Laporan Uang Masuk dan Uang Keluar

## 1. Ringkasan
Fitur ini menambahkan kemampuan ekspor laporan transaksi Uang Masuk (`Debit`) dan Uang Keluar (`Credit`) ke dalam format spreadsheet Microsoft Excel (`.xlsx`) menggunakan library `maatwebsite/excel`. Ekspor dilakukan berdasarkan rentang tanggal yang telah difilter oleh pengguna, menghasilkan laporan dengan format rapi, header terstruktur, dan baris total nominal di bagian akhir.

## 2. Kebutuhan & Perilaku Sistem
1. **Pemicu Ekspor**:
   - Pengguna melakukan filter rentang tanggal pada halaman Laporan Uang Masuk (`/account/laporan_debit`) atau Laporan Uang Keluar (`/account/laporan_credit`).
   - Ketika data hasil filter tampil di layar, sebuah tombol hijau **EXPORT EXCEL** (`<i class="fa fa-file-excel"></i> EXPORT EXCEL`) akan muncul di sebelah kanan card header tabel laporan.
   - Mengklik tombol tersebut akan mengunduh file `.xlsx` berisi seluruh transaksi pada rentang tanggal tersebut (tanpa pagination).
2. **Isolasi Data Pengguna**:
   - Seluruh query laporan dan ekspor wajib memfilter transaksi berdasarkan `user_id` pengguna yang sedang login (`Auth::id()`).
3. **Format & Isi File Excel**:
   - **Laporan Uang Masuk**:
     - Judul: `LAPORAN UANG MASUK - PEMPEK 755`
     - Periode: `Periode: [tanggal_awal] s/d [tanggal_akhir]`
     - Kolom: `NO.`, `TANGGAL`, `KATEGORI`, `BARANG TERJUAL`, `QTY`, `NOMINAL`, `KETERANGAN`.
     - Baris Penutup: Baris `TOTAL` nominal keseluruhan.
   - **Laporan Uang Keluar**:
     - Judul: `LAPORAN UANG KELUAR - PEMPEK 755`
     - Periode: `Periode: [tanggal_awal] s/d [tanggal_akhir]`
     - Kolom: `NO.`, `TANGGAL`, `KATEGORI`, `NOMINAL`, `KETERANGAN`.
     - Baris Penutup: Baris `TOTAL` nominal keseluruhan.
4. **Penamaan File**:
   - Uang Masuk: `laporan-uang-masuk-YYYY-MM-DD-sd-YYYY-MM-DD.xlsx`
   - Uang Keluar: `laporan-uang-keluar-YYYY-MM-DD-sd-YYYY-MM-DD.xlsx`

## 3. Desain Komponen & Arsitektur

### 3.1 Paket & Dependensi
- `maatwebsite/excel`: `^3.1` (menggunakan engine `PhpSpreadsheet` v1.30 kompatibel PHP 7.4).
- Service provider dan alias `Excel` otomatis terdaftar via package discovery Laravel.

### 3.2 Export Classes
1. **`App\Exports\DebitExport`**:
   - Menerima parameter `$tanggal_awal` dan `$tanggal_akhir`.
   - Mengambil data debit dengan join `categories_debit` dan `stock_barang` untuk user yang login.
   - Mengimplementasikan `Illuminate\Contracts\View\View`, `Maatwebsite\Excel\Concerns\FromView`, dan `Maatwebsite\Excel\Concerns\ShouldAutoSize`.
   - Menghitung `$total_nominal = $debit->sum('nominal')`.
   - Me-render view `account.laporan_debit.excel`.
2. **`App\Exports\CreditExport`**:
   - Menerima parameter `$tanggal_awal` dan `$tanggal_akhir`.
   - Mengambil data credit dengan join `categories_credit` untuk user yang login.
   - Mengimplementasikan `FromView` dan `ShouldAutoSize`.
   - Menghitung `$total_nominal = $credit->sum('nominal')`.
   - Me-render view `account.laporan_credit.excel`.

### 3.3 Blade Excel Views
- `resources/views/account/laporan_debit/excel.blade.php`: Format HTML table khusus yang dirender oleh PhpSpreadsheet menjadi file `.xlsx`.
- `resources/views/account/laporan_credit/excel.blade.php`: Format HTML table untuk pengeluaran.

### 3.4 Routing & Controller
- **Rute Web (`routes/web.php`)**:
  - `Route::get('/laporan_debit/export', 'account\LaporanDebitController@export')->name('account.laporan_debit.export');`
  - `Route::get('/laporan_credit/export', 'account\LaporanCreditController@export')->name('account.laporan_credit.export');`
- **Controller**:
  - `LaporanDebitController`: Method `export(Request $request)` dan perbaikan `check()` agar menyaring `user_id`.
  - `LaporanCreditController`: Method `export(Request $request)` dan perbaikan `check()` agar menyaring `user_id`.

## 4. Rencana Pengujian
1. Verifikasi instalasi paket via composer.
2. Pengujian otomatis di `tests/Feature/LaporanExportTest.php`:
   - Menguji download export debit menghasilkan file excel binary (HTTP 200) dengan header content-disposition yang benar.
   - Menguji download export credit menghasilkan file excel binary (HTTP 200).
   - Memastikan hanya data milik user yang bersangkutan yang diekspor.
