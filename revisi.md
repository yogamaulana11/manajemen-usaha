# Dokumen Spesifikasi Kebutuhan Sistem & Catatan Revisi Dosen
**Sistem Informasi Pengelolaan Inventaris & Kasir Pempek**

---

## 1. Ringkasan Eksekutif (Context & Overview)
Dokumen ini merangkum perbaikan arsitektur data dan alur transaksi sistem kasir serta inventaris pempek berdasarkan catatan review dosen. Fokus revisi mencakup standardisasi tabel master data, mekanisme pencatatan transaksi multi-item (header-detail) pada modul produksi dan penjualan, serta otomasi pergerakan inventaris (*stock in / stock out*).

---

## 2. Modul Master Data (`master_pempek`)

Modul master data berfungsi sebagai basis referensi utama (*single source of truth*) untuk seluruh item produk pempek.

### Skema Data (Attributes)
| Field | Tipe Data | Keterangan / Constraint |
|---|---|---|
| `kode_pempek` | VARCHAR(20) | **Primary Key**, kode unik barang (misal: `PMP-001`) |
| `nama_pempek` | VARCHAR(100) | Nama varian pempek (misal: Pempek Lenjer, Kapal Selam) |
| `jenis_ikan` | VARCHAR(50) | Jenis bahan baku ikan (misal: Tenggiri, Gabus, Kakap) |
| `harga` | DECIMAL(12,2) | Harga jual satuan |
| `foto` | VARCHAR(255) | Path / URL file gambar produk |
| `stok` | INT | Sisa kuantitas terkini (teragregasi otomatis) |

### Business Rules:
- Nilai `stok` tidak diedit secara manual di form master jika transaksi sudah berjalan.
- Nilai `stok` bertambah saat terjadi transaksi pada modul **Produksi Pempek**.
- Nilai `stok` berkurang saat terjadi transaksi pada modul **Penjualan / Kasir**.

---

## 3. Modul Transaksi Produksi (Stock In)

Mencatat penambahan stok barang jadi hasil produksi harian dapur/pabrik.

### Struktur Transaksi: Header-Detail
Catatan dosen menegaskan bahwa **1 faktur produksi dapat memuat beberapa barang sekaligus**.

#### A. Header Transaksi Produksi (`produksi_header`)
* `no_faktur` (PK, VARCHAR): Digenerate otomatis oleh sistem (misal: `PRD-YYYYMMDD-XXXX`).
* `tanggal`: Tanggal transaksi dicatat, terisi otomatis (*current timestamp*).
* `keterangan`: Catatan opsional produksi.

#### B. Detail Transaksi Produksi (`produksi_detail`)
* `id_detail` (PK, BIGINT / Auto-increment)
* `no_faktur` (FK merujuk ke `produksi_header.no_faktur`)
* `kode_pempek` (FK merujuk ke `master_pempek.kode_pempek`)
* `jumlah_produksi` (INT): Kuantitas barang yang diproduksi.

### Kebutuhan UI / UX:
* **Mekanisme Input Multi-Barang:** User dapat memilih/mengetik `kode_pempek` dan `jumlah_produksi`, lalu menekan tombol **Enter** untuk langsung menambahkan baris baru (*append row*) pada faktur yang sama tanpa me-reload form.
* **Mutasi Stok:** Saat faktur produksi disimpan:
  $$\text{stok}_{\text{master}} = \text{stok}_{\text{master}} + \text{jumlah\_produksi}$$

---

## 4. Modul Transaksi Penjualan / Kasir (Stock Out)

Mencatat transaksi penjualan langsung kepada pelanggan di kasir.

### Struktur Transaksi: Header-Detail
1 faktur penjualan dapat memuat beberapa item pesanan pelanggan.

#### A. Header Transaksi Penjualan (`penjualan_header`)
* `no_faktur` (PK, VARCHAR): Nomor nota penjualan, otomatis digenerate (misal: `INV-YYYYMMDD-XXXX`).
* `tanggal_jual`: Tanggal & waktu transaksi kasir, otomatis terisi saat ini.
* `total_bayar`: Akumulasi nilai akhir belanja.

#### B. Detail Transaksi Penjualan (`penjualan_detail`)
* `id_detail` (PK, BIGINT / Auto-increment)
* `no_faktur` (FK merujuk ke `penjualan_header.no_faktur`)
* `kode_pempek` (FK merujuk ke `master_pempek.kode_pempek`)
* `harga`: Otomatis diambil (*auto-populate*) dari `master_pempek.harga` saat kode dipilih.
* `jumlah_jual` (INT): Kuantitas yang dibeli konsumen.
* `subtotal`: Terhitung otomatis: $\text{harga} \times \text{jumlah\_jual}$.

### Kebutuhan UI / UX & Validasi:
* **Lookup Otomatis:** Pemilihan `kode_pempek` langsung memunculkan nama item dan harga satuan secara realtime.
* **Validasi Stok:** Sistem wajib memvalidasi `jumlah_jual <= stok_tersedia`. Jika stok tidak mencukupi, sistem menampilkan notifikasi error.
* **Mutasi Stok:** Saat faktur penjualan berhasil diproses:
  $$\text{stok}_{\text{master}} = \text{stok}_{\text{master}} - \text{jumlah\_jual}$$

---

## 5. Ringkasan Hubungan Antar Entitas (ERD Logic)

```text
[ master_pempek ]
   │
   ├── (1:N) ──< [ produksi_detail ]  >── (N:1) ── [ produksi_header ]
   │                 ▲
   │                 └── Event: Stok Bertambah (+)
   │
   └── (1:N) ──< [ penjualan_detail ] >── (N:1) ── [ penjualan_header ]
                     ▲
                     └── Event: Stok Berkurang (-)
```

---
*Dokumen ini dirancang untuk dapat di-parse dan diimplementasikan langsung oleh engine pengembang AI / Antigravity.*
