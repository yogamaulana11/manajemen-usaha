# Desain Sistem: Pengelolaan Inventaris & Kasir Pempek (Multi-Item Header-Detail)

Dokumen ini merangkum arsitektur, skema data, alur bisnis, antarmuka pengguna, dan strategi pengujian untuk modul Master Data Pempek, Transaksi Produksi (Stock In), dan Transaksi Penjualan / Kasir (Stock Out) sesuai catatan revisi dosen pada `revisi.md`.

---

## 1. Tujuan & Ruang Lingkup

1. **Standardisasi Master Data Pempek (`master_pempek`)**: Menyediakan single source of truth untuk katalog produk pempek, harga, dan stok terkini.
2. **Transaksi Produksi Multi-Item (`produksi_header` & `produksi_detail`)**: Mendukung pencatatan 1 nomor faktur untuk banyak varian barang hasil produksi dapur sekaligus dengan mekanisme *append row* via Enter/klik, serta penambahan stok otomatis (*Stock In*).
3. **Transaksi Penjualan Kasir Multi-Item (`penjualan_header` & `penjualan_detail`)**: Mendukung kasir POS multi-item dengan auto-lookup harga dan nama produk, validasi stok tersedia, pengurangan stok otomatis (*Stock Out*), cetak struk nota kasir, dan integrasi otomatis ke pencatatan Uang Masuk (`debit`) untuk laporan keuangan.

---

## 2. Skema Basis Data

### A. Tabel `master_pempek`
* `kode_pempek` (VARCHAR(20), PRIMARY KEY): Kode unik produk (contoh: `PMP-001`, `PMP-002`).
* `user_id` (BIGINT UNSIGNED, FOREIGN KEY merujuk ke `users.id` `ON DELETE RESTRICT`): Menjamin multi-tenant per pengguna.
* `nama_pempek` (VARCHAR(100)): Nama varian produk.
* `jenis_ikan` (VARCHAR(50)): Jenis bahan baku ikan (misal: Tenggiri, Gabus, Kakap, Parang-parang).
* `harga` (DECIMAL(12,2)): Harga jual satuan.
* `foto` (VARCHAR(255), NULLABLE): Path gambar produk di storage/public.
* `stok` (INT, DEFAULT 0): Kuantitas terkini, tidak dapat diubah manual setelah ada transaksi.
* `created_at` & `updated_at` (TIMESTAMP).

### B. Tabel `produksi_header`
* `no_faktur` (VARCHAR(30), PRIMARY KEY): Nomor faktur unik format `PRD-YYYYMMDD-XXXX`.
* `user_id` (BIGINT UNSIGNED, FOREIGN KEY merujuk ke `users.id`).
* `tanggal` (DATETIME): Waktu transaksi produksi dicatat.
* `keterangan` (TEXT, NULLABLE): Catatan produksi dapur.
* `created_at` & `updated_at` (TIMESTAMP).

### C. Tabel `produksi_detail`
* `id_detail` (BIGINT UNSIGNED AUTO_INCREMENT, PRIMARY KEY).
* `no_faktur` (VARCHAR(30), FOREIGN KEY merujuk ke `produksi_header.no_faktur` `ON DELETE CASCADE`).
* `kode_pempek` (VARCHAR(20), FOREIGN KEY merujuk ke `master_pempek.kode_pempek` `ON UPDATE CASCADE ON DELETE RESTRICT`).
* `jumlah_produksi` (INT): Kuantitas barang yang diproduksi ($\ge 1$).
* `created_at` & `updated_at` (TIMESTAMP).

### D. Tabel `penjualan_header`
* `no_faktur` (VARCHAR(30), PRIMARY KEY): Nomor nota unik format `INV-YYYYMMDD-XXXX`.
* `user_id` (BIGINT UNSIGNED, FOREIGN KEY merujuk ke `users.id`).
* `tanggal_jual` (DATETIME): Waktu transaksi kasir.
* `total_bayar` (DECIMAL(12,2)): Total tagihan belanja.
* `bayar` (DECIMAL(12,2)): Uang tunai yang diserahkan pelanggan.
* `kembalian` (DECIMAL(12,2)): Uang kembalian.
* `catatan` (TEXT, NULLABLE): Catatan pesanan kasir.
* `created_at` & `updated_at` (TIMESTAMP).

### E. Tabel `penjualan_detail`
* `id_detail` (BIGINT UNSIGNED AUTO_INCREMENT, PRIMARY KEY).
* `no_faktur` (VARCHAR(30), FOREIGN KEY merujuk ke `penjualan_header.no_faktur` `ON DELETE CASCADE`).
* `kode_pempek` (VARCHAR(20), FOREIGN KEY merujuk ke `master_pempek.kode_pempek` `ON UPDATE CASCADE ON DELETE RESTRICT`).
* `harga` (DECIMAL(12,2)): Harga satuan produk saat transaksi.
* `jumlah_jual` (INT): Kuantitas yang dibeli ($\ge 1$).
* `subtotal` (DECIMAL(12,2)): $\text{harga} \times \text{jumlah\_jual}$.
* `created_at` & `updated_at` (TIMESTAMP).

---

## 3. Relasi Model Eloquent

```text
[ User ]
   ├── hasMany -> [ MasterPempek ]
   ├── hasMany -> [ ProduksiHeader ]
   └── hasMany -> [ PenjualanHeader ]

[ MasterPempek ] (PK: kode_pempek)
   ├── hasMany -> [ ProduksiDetail ]
   └── hasMany -> [ PenjualanDetail ]

[ ProduksiHeader ] (PK: no_faktur)
   └── hasMany -> [ ProduksiDetail ] (FK: no_faktur)

[ PenjualanHeader ] (PK: no_faktur)
   └── hasMany -> [ PenjualanDetail ] (FK: no_faktur)
```

Konfigurasi kunci:
* `MasterPempek`, `ProduksiHeader`, `PenjualanHeader` memiliki `$incrementing = false` dan `protected $keyType = 'string'`.

---

## 4. Alur Bisnis & Transaksi Atomik

### 4.1. Transaksi Produksi (Stock In)
1. **Validasi Request**:
   * Minimal terdapat 1 item dalam array `items`.
   * Setiap item memiliki `kode_pempek` yang valid (terdaftar pada user aktif) dan `jumlah_produksi >= 1`.
2. **Eksekusi Atomik (`DB::transaction`)**:
   * Generate `no_faktur` unik (`PRD-YYYYMMDD-XXXX`).
   * Simpan entitas `ProduksiHeader`.
   * Looping `items`:
     * Simpan entitas `ProduksiDetail`.
     * Update stok master dengan row locking:
       ```php
       MasterPempek::where('kode_pempek', $item['kode_pempek'])
           ->where('user_id', Auth::id())
           ->lockForUpdate()
           ->increment('stok', $item['jumlah_produksi']);
       ```

### 4.2. Transaksi Penjualan Kasir (Stock Out) & Integrasi Debit
1. **Validasi Request & Stok**:
   * Minimal terdapat 1 item belanja.
   * Ambil data harga dan stok asli langsung dari `master_pempek` milik user aktif di server side.
   * Pastikan `jumlah_jual <= stok`. Jika ada item dengan kuantitas melebihi stok, lempar error validasi dan batalkan transaksi.
   * Pastikan nominal `bayar >= total_bayar`.
2. **Eksekusi Atomik (`DB::transaction`)**:
   * Generate `no_faktur` unik (`INV-YYYYMMDD-XXXX`).
   * Hitung total nilai belanja dan nilai kembalian.
   * Simpan entitas `PenjualanHeader`.
   * Looping `items`:
     * Simpan entitas `PenjualanDetail` dengan harga dan subtotal terverifikasi.
     * Kurangi stok master dengan row locking:
       ```php
       MasterPempek::where('kode_pempek', $item['kode_pempek'])
           ->where('user_id', Auth::id())
           ->lockForUpdate()
           ->decrement('stok', $item['jumlah_jual']);
       ```
   * **Sinkronisasi Uang Masuk (`debit`)**:
     * Cari atau buat kategori debit default `Penjualan Pempek` untuk user aktif.
     * Masukkan catatan baru ke tabel `debit`:
       * `category_id`: ID kategori "Penjualan Pempek"
       * `nominal`: `total_bayar`
       * `debit_date`: `tanggal_jual`
       * `description`: `Penjualan Kasir Faktur: INV-YYYYMMDD-XXXX`
     * Hal ini otomatis memperbarui statistik dashboard dan laporan uang masuk tanpa intervensi manual.

---

## 5. Rancangan Antarmuka Pengguna (UI/UX)

1. **Navigasi Sidebar**:
   * Grup Menu: `MANAJEMEN PEMPEK`
     * Submenu 1: `Master Pempek` (Route: `account.master_pempek.index`)
     * Submenu 2: `Produksi (Stock In)` (Route: `account.produksi.index`)
     * Submenu 3: `Kasir Penjualan` (Route: `account.penjualan.index`)
2. **Halaman Master Pempek**:
   * Daftar produk dengan gambar, kode, nama, jenis bahan ikan, harga (format Rupiah), indikator ketersediaan stok, dan aksi (Edit / Hapus).
   * Form tambah/edit dengan fitur upload gambar dan auto-generate kode produk berikutnya.
3. **Halaman Form Produksi (Stock In)**:
   * Header faktur (no faktur, tanggal, keterangan).
   * Input interaktif: Dropdown produk + kuantitas.
   * Tekan tombol **Enter** atau klik **Tambah** untuk menambahkan item ke baris tabel tanpa reload.
   * Tabel daftar barang produksi sementara dengan opsi hapus baris.
4. **Halaman Kasir Penjualan (POS)**:
   * Area pemilihan barang dengan auto-lookup / search realtime.
   * Keranjang belanja realtime (nama barang, harga satuan, input kuantitas dengan validasi batas stok, subtotal, tombol hapus item).
   * Panel pembayaran: total belanja, input nominal uang tunai, kalkulasi otomatis kembalian secara realtime.
   * Modal Struk / Cetak Nota: format struk kasir thermal 58mm/80mm siap print (`window.print()`).

---

## 6. Rencana Pengujian Otomatis

1. **`MasterPempekTest.php`**:
   * Test create, update, delete master pempek.
   * Test kode unik dan pembatasan isolasi antar user.
2. **`ProduksiPempekTest.php`**:
   * Test pembuatan faktur produksi multi-item.
   * Verifikasi mutasi stok bertambah di `master_pempek`.
3. **`PenjualanKasirTest.php`**:
   * Test transaksi kasir multi-item berhasil mengurangi stok `master_pempek`.
   * Test validasi gagal bila stok tidak mencukupi.
   * Test pembuatan otomatis entri `debit` dan kecocokan nominal.
