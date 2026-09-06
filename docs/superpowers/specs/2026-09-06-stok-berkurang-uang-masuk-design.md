# Desain Spesifikasi: Pengurangan Stok Barang Saat Transaksi Uang Masuk

## 1. Ringkasan
Fitur ini mengintegrasikan modul Uang Masuk (`Debit`) dengan modul Stok Barang (`StockBarang`). Saat pengguna mencatat transaksi uang masuk yang berasal dari penjualan barang, pengguna dapat memilih barang dan memasukkan jumlah kuantitas (`qty`). Sistem secara otomatis akan mengurangi sisa stok barang terkait, serta menjaga konsistensi data saat transaksi diubah atau dihapus.

## 2. Kebutuhan & Perilaku Sistem
1. **Pemilihan Barang**:
   - Di form Uang Masuk (Create & Edit), input pilihan barang bersifat opsional.
   - Jika pengguna memilih barang, maka jumlah kuantitas (`qty`) wajib diisi (minimal 1).
   - Jika pengguna tidak memilih barang (misal: penambahan modal, penerimaan piutang non-stok), transaksi debit dicatat biasa tanpa memengaruhi stok.
2. **Validasi Stok**:
   - Kuantitas yang dimasukkan tidak boleh melebihi sisa stok barang yang tersedia. Jika melebihi, sistem menampilkan pesan validasi error dan membatalkan penyimpanan.
3. **Pengurangan Stok Otomatis**:
   - Saat transaksi berhasil disimpan, `jumlah_stok` pada tabel `stock_barang` berkurang sebanyak `qty` yang diinput.
   - Kolom `tanggal_update` pada barang diperbarui ke waktu saat ini.
4. **Penyesuaian Saat Edit (Update)**:
   - Jika transaksi debit diedit:
     - Stok barang lama dikembalikan terlebih dahulu (+qty lama).
     - Validasi stok barang baru dilakukan terhadap stok terkini.
     - Stok barang baru dikurangi sesuai dengan qty baru (-qty baru).
5. **Pengembalian Stok Saat Hapus (Destroy)**:
   - Jika transaksi debit yang memiliki relasi barang dihapus, stok barang otomatis dikembalikan (+qty).
6. **Integritas Transaksi (ACID)**:
   - Semua operasi simpan, update, dan hapus yang melibatkan stok dibungkus dalam `DB::transaction()` untuk mencegah data parsial/tidak konsisten.

## 3. Desain Database & Model

### 3.1 Migrasi Database
File: `database/migrations/YYYY_MM_DD_HHMMSS_add_stock_id_and_qty_to_debit_table.php`
- Tambah kolom pada tabel `debit`:
  - `stock_id`: `unsignedBigInteger('stock_id')->nullable()->after('category_id')`
  - `qty`: `integer('qty')->nullable()->after('stock_id')`
  - Foreign key: `stock_id` mereferensikan `id` pada `stock_barang` dengan `onDelete('SET NULL')` dan `onUpdate('CASCADE')`.

### 3.2 Model `App\Debit`
- Update `$fillable`:
  ```php
  protected $fillable = [
      'user_id',
      'category_id',
      'stock_id',
      'qty',
      'nominal',
      'description',
      'debit_date'
  ];
  ```
- Tambahkan relasi:
  ```php
  public function stock()
  {
      return $this->belongsTo(StockBarang::class, 'stock_id');
  }
  ```

### 3.3 Model `App\StockBarang`
- Tambahkan relasi:
  ```php
  public function debits()
  {
      return $this->hasMany(Debit::class, 'stock_id');
  }
  ```

## 4. Logika Controller (`App\Http\Controllers\account\DebitController`)

### 4.1 `create()`
- Mengambil data kategori debit milik user.
- Mengambil data stok barang milik user (`StockBarang::where('user_id', Auth::id())->where('jumlah_stok', '>', 0)->orderBy('nama_barang', 'ASC')->get()`).
- Mengirimkan variabel `$stocks` ke view `account.debit.create`.

### 4.2 `store(Request $request)`
- Validasi:
  - `nominal`: required
  - `debit_date`: required
  - `category_id`: required
  - `description`: required
  - `stock_id`: nullable|exists:stock_barang,id
  - `qty`: required_with:stock_id|nullable|integer|min:1
- Pengecekan stok:
  - Jika `$request->filled('stock_id')`:
    - Ambil data barang: `StockBarang::where('id', $request->stock_id)->where('user_id', Auth::id())->lockForUpdate()->firstOrFail()`.
    - Jika `$barang->jumlah_stok < $request->qty`, gagalkan dengan error flash / validator error: "Stok barang tidak mencukupi! Sisa stok saat ini: {$barang->jumlah_stok}."
- Transaksi database:
  - `DB::transaction(function() { ... })`:
    - Simpan data `Debit`.
    - Kurangi `jumlah_stok` barang: `$barang->decrement('jumlah_stok', $request->qty)`.
    - Update `tanggal_update` barang: `$barang->update(['tanggal_update' => now()])`.

### 4.3 `edit(Debit $debit)`
- Mengambil data kategori debit.
- Mengambil seluruh barang milik user (termasuk yang stok 0, karena barang yang bersangkutan mungkin stoknya habis setelah transaksi ini).
- Mengirimkan `$stocks` dan `$debit` ke view `account.debit.edit`.

### 4.4 `update(Request $request, Debit $debit)`
- Validasi form sama seperti `store`.
- Transaksi database `DB::transaction()`:
  - Jika debit sebelumnya memiliki `stock_id` dan `qty`:
    - Cari barang lama dan kembalikan stoknya: `StockBarang::where('id', $debit->stock_id)->increment('jumlah_stok', $debit->qty)`.
  - Jika form update menyertakan `stock_id` dan `qty`:
    - Ambil barang baru dengan `lockForUpdate()`.
    - Cek apakah sisa stok mencukupi. Jika tidak cukup, lempar exception / rollback.
    - Kurangi stok barang baru: `$barangBaru->decrement('jumlah_stok', $request->qty)`.
    - Update `tanggal_update` barang baru.
  - Update data `Debit`.

### 4.5 `destroy($id)`
- Transaksi database `DB::transaction()`:
  - Cari data debit.
  - Jika memiliki `stock_id` dan `qty`:
    - Kembalikan stok: `StockBarang::where('id', $debit->stock_id)->increment('jumlah_stok', $debit->qty)`.
  - Hapus data debit.

### 4.6 `index()` & `search()`
- Lakukan eager loading atau join dengan `stock_barang` (`LEFT JOIN stock_barang ON debit.stock_id = stock_barang.id`) agar nama barang dan kuantitas dapat ditampilkan di daftar.

## 5. Tampilan Antarmuka (Blade Views)

1. **`resources/views/account/debit/create.blade.php`**:
   - Menambahkan row form berisi:
     - `stock_id`: Select2 dropdown daftar barang dengan format nama `{{ $item->nama_barang }} (Sisa Stok: {{ $item->jumlah_stok }})`.
     - `qty`: Input number (min=1).
2. **`resources/views/account/debit/edit.blade.php`**:
   - Menambahkan row form `stock_id` dan `qty` dengan nilai `old()` atau `$debit->stock_id` & `$debit->qty`.
3. **`resources/views/account/debit/index.blade.php`**:
   - Menambahkan keterangan barang & qty pada kolom tabel debit (misal: badge `[Nama Barang] x [qty]`).

## 6. Verifikasi & Pengujian
1. Menjalankan migrasi `php artisan migrate`.
2. Pengujian penambahan uang masuk dengan memilih barang:
   - Pastikan stok di tabel `stock_barang` berkurang sesuai qty.
   - Pastikan transaksi debit tersimpan dengan `stock_id` dan `qty`.
3. Pengujian validasi saat qty melebihi stok:
   - Pastikan transaksi ditolak dan stok tidak berubah.
4. Pengujian penambahan uang masuk tanpa memilih barang:
   - Pastikan transaksi debit tersimpan dengan `stock_id = null` dan stok barang tidak berubah.
5. Pengujian update transaksi debit:
   - Ubah qty atau ganti barang, pastikan stok barang lama dikembalikan dan barang baru dipotong secara akurat.
6. Pengujian hapus transaksi debit:
   - Hapus debit berelasi barang, pastikan stok dikembalikan ke `stock_barang`.
