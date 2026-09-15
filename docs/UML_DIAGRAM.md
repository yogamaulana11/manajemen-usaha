# Dokumentasi Diagram UML - Sistem Manajemen Usaha & Kasir Pempek

Dokumen ini memuat diagram UML lengkap untuk proyek **Manajemen Usaha & Kasir Pempek** yang dibuat menggunakan format Markdown Mermaid, mencakup fitur inventaris pempek multi-item dan kasir POS sesuai revisi dosen.

---

## 1. Use Case Diagram
Diagram ini memetakan peran pengguna (User / Pemilik Usaha / Kasir) serta fitur-fitur yang tersedia di dalam aplikasi.

```mermaid
flowchart LR
    User(["👤 User / Pemilik Usaha / Kasir"])

    subgraph Auth["🔐 Modul Autentikasi"]
        UC1["Login & Logout"]
        UC2["Ganti Password & Profil"]
    end

    subgraph DashboardMod["📊 Modul Dashboard"]
        UC3["Lihat Ringkasan Saldo & Transaksi"]
        UC4["Lihat Grafik Statistik Keuangan 1 Tahun"]
        UC5["Filter Tahun Statistik"]
    end

    subgraph PempekMod["🐟 Modul Manajemen Pempek"]
        UC_P1["Kelola Master Pempek (CRUD, Foto & Harga)"]
        UC_P2["Catat Produksi Pempek Multi-Item (Stock In)"]
        UC_P3["Transaksi Kasir Penjualan POS Multi-Item (Stock Out)"]
        UC_P4["Cetak Struk Nota Pembayaran Kasir"]
        UC_P5["Auto Sinkronisasi Uang Masuk ke Debit"]
    end

    subgraph MasterData["📦 Modul Master Data Lama"]
        UC6["Kelola Kategori Uang Masuk (CRUD)"]
        UC7["Kelola Kategori Uang Keluar (CRUD)"]
        UC8["Kelola Stok Barang Lama (CRUD & Search)"]
    end

    subgraph Transaksi["💰 Modul Transaksi Keuangan"]
        UC9["Catat Uang Masuk Manual (Debit)"]
        UC11["Catat Uang Keluar (Credit)"]
    end

    subgraph Laporan["📑 Modul Laporan & Ekspor"]
        UC12["Filter Laporan Uang Masuk"]
        UC13["Filter Laporan Uang Keluar"]
        UC14["Export Laporan Uang Masuk ke Excel (.xlsx)"]
        UC15["Export Laporan Uang Keluar ke Excel (.xlsx)"]
    end

    User --> UC1
    User --> UC2
    User --> UC3
    User --> UC4
    User --> UC5

    User --> UC_P1
    User --> UC_P2
    User --> UC_P3
    UC_P3 -.->|"<<extend>>"| UC_P4
    UC_P3 -.->|"<<include>>"| UC_P5

    User --> UC6
    User --> UC7
    User --> UC8
    User --> UC9
    User --> UC11
    User --> UC12
    UC12 -.->|"<<extend>>"| UC14
    User --> UC13
    UC13 -.->|"<<extend>>"| UC15
```

---

## 2. Class Diagram
Diagram struktur kelas (Model Eloquent, Controller, Export) beserta relasinya.

```mermaid
classDiagram
    %% Eloquent Models
    class User {
        +int id
        +string full_name
        +string email
        +string username
        +string password
        +debits() HasMany
        +credits() HasMany
        +masterPempeks() HasMany
        +produksiHeaders() HasMany
        +penjualanHeaders() HasMany
    }

    class MasterPempek {
        +string kode_pempek (PK)
        +int user_id (FK)
        +string nama_pempek
        +string jenis_ikan
        +decimal harga
        +string foto
        +int stok
        +user() BelongsTo
        +produksiDetails() HasMany
        +penjualanDetails() HasMany
    }

    class ProduksiHeader {
        +string no_faktur (PK)
        +int user_id (FK)
        +datetime tanggal
        +string keterangan
        +user() BelongsTo
        +details() HasMany
    }

    class ProduksiDetail {
        +int id_detail (PK)
        +string no_faktur (FK)
        +string kode_pempek (FK)
        +int jumlah_produksi
        +header() BelongsTo
        +pempek() BelongsTo
    }

    class PenjualanHeader {
        +string no_faktur (PK)
        +int user_id (FK)
        +datetime tanggal_jual
        +decimal total_bayar
        +decimal bayar
        +decimal kembalian
        +string catatan
        +user() BelongsTo
        +details() HasMany
    }

    class PenjualanDetail {
        +int id_detail (PK)
        +string no_faktur (FK)
        +string kode_pempek (FK)
        +decimal harga
        +int jumlah_jual
        +decimal subtotal
        +header() BelongsTo
        +pempek() BelongsTo
    }

    class Debit {
        +int id
        +int user_id (FK)
        +int category_id (FK)
        +bigint nominal
        +text description
        +datetime debit_date
        +user() BelongsTo
        +category() BelongsTo
    }

    %% Controller Classes
    class MasterPempekController {
        +index() View
        +create() View
        +store(Request) RedirectResponse
        +edit(string kode) View
        +update(Request, string kode) RedirectResponse
        +destroy(string kode) JsonResponse
        +search(Request) View
    }

    class ProduksiPempekController {
        +index() View
        +create() View
        +store(Request) RedirectResponse
        +show(string no_faktur) View
        +search(Request) View
    }

    class PenjualanKasirController {
        +index() View
        +create() View
        +store(Request) JsonResponse
        +show(string no_faktur) View
        +struk(string no_faktur) View
        +search(Request) View
    }

    %% Relationships
    User "1" --> "0..*" MasterPempek : hasMany
    User "1" --> "0..*" ProduksiHeader : hasMany
    User "1" --> "0..*" PenjualanHeader : hasMany
    User "1" --> "0..*" Debit : hasMany

    MasterPempek "1" --> "0..*" ProduksiDetail : hasMany
    MasterPempek "1" --> "0..*" PenjualanDetail : hasMany

    ProduksiHeader "1" --> "1..*" ProduksiDetail : hasMany
    PenjualanHeader "1" --> "1..*" PenjualanDetail : hasMany

    MasterPempekController ..> MasterPempek : manages
    ProduksiPempekController ..> ProduksiHeader : manages
    ProduksiPempekController ..> MasterPempek : increments stock
    PenjualanKasirController ..> PenjualanHeader : manages
    PenjualanKasirController ..> MasterPempek : decrements stock
    PenjualanKasirController ..> Debit : creates income
```

---

## 3. Entity Relationship Diagram (ERD)
Diagram relasi basis data fisik dari database MySQL aplikasi manajemen usaha pempek.

```mermaid
erDiagram
    users ||--o{ master_pempek : "memiliki"
    users ||--o{ produksi_header : "mencatat"
    users ||--o{ penjualan_header : "melayani"
    users ||--o{ debit : "memiliki"
    users ||--o{ credit : "memiliki"

    master_pempek ||--o{ produksi_detail : "diproduksi_pada"
    produksi_header ||--|{ produksi_detail : "memuat"

    master_pempek ||--o{ penjualan_detail : "dijual_pada"
    penjualan_header ||--|{ penjualan_detail : "memuat"

    categories_debit ||--o{ debit : "mengelompokkan"

    master_pempek {
        string kode_pempek PK
        bigint user_id FK
        string nama_pempek
        string jenis_ikan
        decimal harga
        string foto
        int stok
        datetime created_at
        datetime updated_at
    }

    produksi_header {
        string no_faktur PK
        bigint user_id FK
        datetime tanggal
        text keterangan
        datetime created_at
        datetime updated_at
    }

    produksi_detail {
        bigint id_detail PK
        string no_faktur FK
        string kode_pempek FK
        int jumlah_produksi
        datetime created_at
        datetime updated_at
    }

    penjualan_header {
        string no_faktur PK
        bigint user_id FK
        datetime tanggal_jual
        decimal total_bayar
        decimal bayar
        decimal kembalian
        text catatan
        datetime created_at
        datetime updated_at
    }

    penjualan_detail {
        bigint id_detail PK
        string no_faktur FK
        string kode_pempek FK
        decimal harga
        int jumlah_jual
        decimal subtotal
        datetime created_at
        datetime updated_at
    }

    debit {
        bigint id PK
        bigint user_id FK
        bigint category_id FK
        bigint nominal
        text description
        datetime debit_date
        datetime created_at
        datetime updated_at
    }
```

---

## 4. Sequence Diagram: Transaksi Kasir POS & Pemotongan Stok Atomik
Diagram ini menunjukkan interaksi checkout kasir pempek dengan pengecekan stok server-side, mutasi stok atomik, pencatatan otomatis ke Uang Masuk (`debit`), dan cetak struk nota.

```mermaid
sequenceDiagram
    autonumber
    actor Kasir as 👤 Kasir
    participant View as 🖥️ Kasir POS (Blade/jQuery)
    participant Ctrl as ⚙️ PenjualanKasirController
    participant DB as 🗄️ Database (MySQL)

    Kasir->>View: Pilih varian pempek & kuantitas
    View->>View: Hitung realtime total belanja
    Kasir->>View: Masukkan uang pembayaran & Klik "Proses Transaksi"
    View->>Ctrl: POST /account/penjualan (tanggal_jual, items[], bayar, catatan)
    
    activate Ctrl
    Ctrl->>Ctrl: Validasi format request ($this->validate)
    Ctrl->>DB: DB::beginTransaction()
    
    loop Untuk setiap item belanja
        Ctrl->>DB: MasterPempek::where('kode_pempek', kode)->lockForUpdate()->first()
        DB-->>Ctrl: Return data item ($pempek)
        
        alt Stok Tidak Cukup (qty > $pempek->stok)
            Ctrl->>DB: DB::rollBack()
            Ctrl-->>View: Return JSON Error 422 ("Stok tidak mencukupi!")
            View-->>Kasir: Tampilkan SweetAlert Peringatan ❌
        end
    end

    alt Uang Bayar Kurang (bayar < total_bayar)
        Ctrl->>DB: DB::rollBack()
        Ctrl-->>View: Return JSON Error 422 ("Uang pembayaran kurang!")
        View-->>Kasir: Tampilkan SweetAlert Pembayaran Kurang ❌
    end

    %% Jika Semua Valid: Simpan Header & Detail
    Ctrl->>DB: PenjualanHeader::create([no_faktur, total_bayar, bayar, kembalian, ...])
    loop Untuk setiap item belanja
        Ctrl->>DB: PenjualanDetail::create([no_faktur, kode_pempek, harga, jumlah_jual, subtotal])
        Ctrl->>DB: $pempek->decrement('stok', jumlah_jual)
    end

    %% Integrasi Otomatis ke Keuangan
    Ctrl->>DB: CategoriesDebit::firstOrCreate(['name' => 'Penjualan Pempek'])
    Ctrl->>DB: Debit::create([nominal: total_bayar, description: 'Penjualan Kasir Faktur: INV-...'])

    Ctrl->>DB: DB::commit()
    Ctrl-->>View: Return JSON 200 (status: success, struk_url, no_faktur, kembalian)
    deactivate Ctrl

    View-->>Kasir: Tampilkan Pop-up Berhasil & Tombol "Cetak Struk" ✅
    Kasir->>View: Klik "Cetak Struk"
    View->>Ctrl: GET /account/penjualan/{no_faktur}/struk
    Ctrl-->>Kasir: Buka jendela cetak struk nota thermal (window.print()) 🖨️
```

---

## 5. Sequence Diagram: Transaksi Produksi Multi-Item (Stock In)
Diagram alur pencatatan produksi dapur dengan mekanisme *append row* dan penambahan stok.

```mermaid
sequenceDiagram
    autonumber
    actor Dapur as 👤 Petugas Dapur
    participant View as 🖥️ Form Produksi (Blade/jQuery)
    participant Ctrl as ⚙️ ProduksiPempekController
    participant DB as 🗄️ Database (MySQL)

    loop Tambah Varian Barang ke Faktur
        Dapur->>View: Pilih varian pempek & ketik jumlah produksi
        Dapur->>View: Tekan Enter / Tombol Tambah
        View->>View: Append baris ke tabel detail tanpa reload halaman
    end

    Dapur->>View: Klik tombol "SIMPAN FAKTUR PRODUKSI"
    View->>Ctrl: POST /account/produksi (tanggal, keterangan, items[])
    activate Ctrl
    Ctrl->>Ctrl: Validasi Request ($this->validate)
    Ctrl->>DB: DB::beginTransaction()

    Ctrl->>DB: ProduksiHeader::create([no_faktur: PRD-..., tanggal, keterangan])
    loop Untuk setiap baris item
        Ctrl->>DB: MasterPempek::where('kode_pempek', kode)->lockForUpdate()->first()
        Ctrl->>DB: ProduksiDetail::create([no_faktur, kode_pempek, jumlah_produksi])
        Ctrl->>DB: $pempek->increment('stok', jumlah_produksi)
    end

    Ctrl->>DB: DB::commit()
    Ctrl-->>View: Redirect ke index produksi dengan pesan sukses
    deactivate Ctrl
    View-->>Dapur: Tampilkan SweetAlert Berhasil & stok terupdate ✅
```

---

## 6. Component Diagram (Arsitektur Sistem)

```mermaid
flowchart TD
    subgraph Client["💻 Client Layer (Browser)"]
        UI["Web Browser (Stisla UI / Bootstrap 4)"]
        JS["Highcharts, Cleave.js & SweetAlert"]
        Print["Thermal Print Dialog (window.print)"]
    end

    subgraph Presentation["🎨 Presentation Layer (Blade Views)"]
        V_Pempek["account.master_pempek.*"]
        V_Prod["account.produksi.*"]
        V_Penj["account.penjualan.* (POS & Struk)"]
        V_Dash["account.dashboard.index"]
        V_Debit["account.debit.*"]
        V_Credit["account.credit.*"]
    end

    subgraph ControllerLayer["⚙️ Controller Layer"]
        C_Pempek["MasterPempekController"]
        C_Prod["ProduksiPempekController"]
        C_Penj["PenjualanKasirController"]
        C_Dash["DashboardController"]
        C_Debit["DebitController"]
    end

    subgraph BusinessLayer["🧠 Business & Integrity Layer"]
        Tx["DB Transaction Manager (lockForUpdate)"]
        SyncDebit["Auto Cashflow Sync Service"]
    end

    subgraph ModelLayer["🏛️ Eloquent Model Layer"]
        M_Pempek["MasterPempek"]
        M_ProdHead["ProduksiHeader"]
        M_ProdDet["ProduksiDetail"]
        M_PenjHead["PenjualanHeader"]
        M_PenjDet["PenjualanDetail"]
        M_Debit["Debit"]
    end

    subgraph DataLayer["🗄️ Persistence Layer"]
        DB[(MySQL Database)]
        Storage[(Public Storage / Image Uploads)]
    end

    UI --> Presentation
    Presentation --> ControllerLayer
    ControllerLayer --> BusinessLayer
    BusinessLayer --> ModelLayer
    ModelLayer --> DataLayer
    C_Penj --> Print
```
