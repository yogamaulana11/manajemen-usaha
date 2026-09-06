# Dokumentasi Diagram UML - Sistem Manajemen Usaha

Dokumen ini memuat diagram UML lengkap untuk proyek **Manajemen Usaha** yang dibuat menggunakan format Markdown Mermaid.

---

## 1. Use Case Diagram
Diagram ini memetakan peran pengguna (User / Pemilik Usaha) serta fitur-fitur yang tersedia di dalam aplikasi.

```mermaid
flowchart LR
    User(["👤 User / Pemilik Usaha"])

    subgraph Auth["🔐 Modul Autentikasi"]
        UC1["Login & Logout"]
        UC2["Ganti Password & Profil"]
    end

    subgraph DashboardMod["📊 Modul Dashboard"]
        UC3["Lihat Ringkasan Saldo & Transaksi"]
        UC4["Lihat Grafik Statistik Keuangan 1 Tahun (Column Chart)"]
        UC5["Filter Tahun Statistik"]
    end

    subgraph MasterData["📦 Modul Master Data"]
        UC6["Kelola Kategori Uang Masuk (CRUD)"]
        UC7["Kelola Kategori Uang Keluar (CRUD)"]
        UC8["Kelola Stok Barang (CRUD & Search)"]
    end

    subgraph Transaksi["💰 Modul Transaksi"]
        UC9["Catat Uang Masuk (Debit)"]
        UC10["Pilih Barang & Qty (Potong Stok Otomatis)"]
        UC11["Catat Uang Keluar (Credit)"]
    end

    subgraph Laporan["📑 Modul Laporan & Ekspor"]
        UC12["Filter Laporan Uang Masuk berdasarkan Periode"]
        UC13["Filter Laporan Uang Keluar berdasarkan Periode"]
        UC14["Export Laporan Uang Masuk ke Excel (.xlsx)"]
        UC15["Export Laporan Uang Keluar ke Excel (.xlsx)"]
    end

    User --> UC1
    User --> UC2
    User --> UC3
    User --> UC4
    User --> UC5
    User --> UC6
    User --> UC7
    User --> UC8
    User --> UC9
    UC9 -.->|"<<include>>"| UC10
    User --> UC11
    User --> UC12
    UC12 -.->|"<<extend>>"| UC14
    User --> UC13
    UC13 -.->|"<<extend>>"| UC15
```

---

## 2. Class Diagram (Arsitektur Model, Controller & Export)
Diagram ini menggambarkan struktur kelas Domain Model Eloquent, Controller, dan Export Handler beserta atribut serta metodenya.

```mermaid
classDiagram
    direction TB

    %% Model User
    class User {
        +int id
        +string full_name
        +string email
        +string username
        +string password
        +string avatar
        +datetime created_at
        +datetime updated_at
        +categories_debit() HasMany
        +categories_credit() HasMany
        +debits() HasMany
        +credits() HasMany
        +stock_barang() HasMany
    }

    %% Model CategoriesDebit
    class CategoriesDebit {
        +int id
        +int user_id
        +string name
        +datetime created_at
        +datetime updated_at
        +user() BelongsTo
        +debits() HasMany
    }

    %% Model CategoriesCredit
    class CategoriesCredit {
        +int id
        +int user_id
        +string name
        +datetime created_at
        +datetime updated_at
        +user() BelongsTo
        +credits() HasMany
    }

    %% Model StockBarang
    class StockBarang {
        +int id
        +int user_id
        +string kategori_barang
        +string nama_barang
        +int jumlah_stok
        +date tanggal_update
        +datetime created_at
        +datetime updated_at
        +user() BelongsTo
        +debits() HasMany
    }

    %% Model Debit
    class Debit {
        +int id
        +int category_id
        +int user_id
        +int stock_id
        +int qty
        +bigint nominal
        +datetime debit_date
        +text description
        +datetime created_at
        +datetime updated_at
        +user() BelongsTo
        +category() BelongsTo
        +stock() BelongsTo
    }

    %% Model Credit
    class Credit {
        +int id
        +int category_id
        +int user_id
        +bigint nominal
        +datetime credit_date
        +text description
        +datetime created_at
        +datetime updated_at
        +user() BelongsTo
        +category() BelongsTo
    }

    %% Controller Classes
    class DebitController {
        +index(Request) View
        +create() View
        +store(Request) RedirectResponse
        +edit(int id) View
        +update(Request, int id) RedirectResponse
        +destroy(int id) JsonResponse
    }

    class LaporanDebitController {
        +index() View
        +check(Request) View
        +export(Request) BinaryFileResponse
    }

    class LaporanCreditController {
        +index() View
        +check(Request) View
        +export(Request) BinaryFileResponse
    }

    class DashboardController {
        +index(Request) View
    }

    %% Export Classes
    class DebitExport {
        #string tanggal_awal
        #string tanggal_akhir
        +__construct(tanggal_awal, tanggal_akhir)
        +view() View
    }

    class CreditExport {
        #string tanggal_awal
        #string tanggal_akhir
        +__construct(tanggal_awal, tanggal_akhir)
        +view() View
    }

    %% Relationships
    User "1" --> "0..*" CategoriesDebit : hasMany
    User "1" --> "0..*" CategoriesCredit : hasMany
    User "1" --> "0..*" StockBarang : hasMany
    User "1" --> "0..*" Debit : hasMany
    User "1" --> "0..*" Credit : hasMany

    CategoriesDebit "1" --> "0..*" Debit : hasMany
    StockBarang "1" --> "0..*" Debit : hasMany
    CategoriesCredit "1" --> "0..*" Credit : hasMany

    DebitController ..> Debit : manages
    DebitController ..> StockBarang : updates stock
    LaporanDebitController ..> DebitExport : creates
    LaporanCreditController ..> CreditExport : creates
    DebitExport ..> Debit : queries
    CreditExport ..> Credit : queries
```

---

## 3. Entity Relationship Diagram (ERD)
Diagram relasi basis data fisik dari database MySQL aplikasi manajemen usaha.

```mermaid
erDiagram
    users ||--o{ categories_debit : "memiliki"
    users ||--o{ categories_credit : "memiliki"
    users ||--o{ stock_barang : "memiliki"
    users ||--o{ debit : "memiliki"
    users ||--o{ credit : "memiliki"

    categories_debit ||--o{ debit : "mengelompokkan"
    stock_barang ||--o{ debit : "berkurang_karena"
    categories_credit ||--o{ credit : "mengelompokkan"

    users {
        bigint id PK
        string full_name
        string email UK
        string username UK
        string password
        string avatar
        datetime created_at
        datetime updated_at
    }

    categories_debit {
        bigint id PK
        bigint user_id FK
        string name
        datetime created_at
        datetime updated_at
    }

    categories_credit {
        bigint id PK
        bigint user_id FK
        string name
        datetime created_at
        datetime updated_at
    }

    stock_barang {
        bigint id PK
        bigint user_id FK
        string kategori_barang
        string nama_barang
        int jumlah_stok
        date tanggal_update
        datetime created_at
        datetime updated_at
    }

    debit {
        bigint id PK
        bigint user_id FK
        bigint category_id FK
        bigint stock_id FK "nullable"
        int qty "nullable"
        bigint nominal
        text description
        datetime debit_date
        datetime created_at
        datetime updated_at
    }

    credit {
        bigint id PK
        bigint user_id FK
        bigint category_id FK
        bigint nominal
        text description
        datetime credit_date
        datetime created_at
        datetime updated_at
    }
```

---

## 4. Sequence Diagram: Transaksi Uang Masuk & Pemotongan Stok
Diagram ini menunjukkan interaksi sistem saat pengguna mencatat transaksi uang masuk dengan pengurangan stok barang yang dilindungi transaksi database ACID.

```mermaid
sequenceDiagram
    autonumber
    actor Pengguna as 👤 Pengguna
    participant View as 🖥️ Form Uang Masuk (Blade/JS)
    participant Ctrl as ⚙️ DebitController
    participant DB as 🗄️ Database (MySQL)

    Pengguna->>View: Pilih Kategori, Barang, Qty & Nominal
    View->>View: Validasi input di sisi klien
    Pengguna->>View: Klik Simpan
    View->>Ctrl: POST /account/debit (nominal, stock_id, qty, debit_date, description)
    
    activate Ctrl
    Ctrl->>Ctrl: Validasi Request ($this->validate)
    
    Ctrl->>DB: DB::beginTransaction()
    
    alt Jika Barang Dipilih (stock_id != null && qty > 0)
        Ctrl->>DB: StockBarang::where('id', stock_id)->lockForUpdate()->first()
        DB-->>Ctrl: Return $stock
        
        alt Stok Tersedia ($stock->jumlah_stok >= qty)
            Ctrl->>DB: $stock->decrement('jumlah_stok', qty)
            Ctrl->>DB: $stock->update(['tanggal_update' => now()])
            Ctrl->>DB: Debit::create([...])
            Ctrl->>DB: DB::commit()
            Ctrl-->>View: Redirect dengan pesan "Data Berhasil Disimpan!"
            View-->>Pengguna: Tampilkan SweetAlert Berhasil ✅
        else Stok Tidak Cukup ($stock->jumlah_stok < qty)
            Ctrl->>DB: DB::rollBack()
            Ctrl-->>View: Redirect back dengan error "Jumlah stok tidak mencukupi!"
            View-->>Pengguna: Tampilkan SweetAlert Gagal ❌
        end
    else Transaksi Tanpa Barang
        Ctrl->>DB: Debit::create([...])
        Ctrl->>DB: DB::commit()
        Ctrl-->>View: Redirect dengan pesan "Data Berhasil Disimpan!"
        View-->>Pengguna: Tampilkan SweetAlert Berhasil ✅
    end
    deactivate Ctrl
```

---

## 5. Sequence Diagram: Filter Laporan & Export ke Excel (.xlsx)
Diagram interaksi saat pengguna memfilter laporan keuangan dan mengunduh berkas spreadsheet Excel.

```mermaid
sequenceDiagram
    autonumber
    actor Pengguna as 👤 Pengguna
    participant View as 🖥️ Halaman Laporan (Blade)
    participant Ctrl as ⚙️ LaporanDebitController
    participant Export as 📄 DebitExport
    participant ExcelLib as 📦 Maatwebsite Excel / PhpSpreadsheet
    participant DB as 🗄️ Database (MySQL)

    %% Fase 1: Filter
    Pengguna->>View: Masukkan Tanggal Awal & Tanggal Akhir
    Pengguna->>View: Klik tombol FILTER
    View->>Ctrl: GET /account/laporan_debit/check?tanggal_awal=...&tanggal_akhir=...
    activate Ctrl
    Ctrl->>DB: Query Debit + Join Categories + Join Stock (Filter user_id & range tanggal)
    DB-->>Ctrl: Return kumpulan data transaksi (paginated)
    Ctrl-->>View: Render view index dengan data & tombol "EXPORT EXCEL"
    deactivate Ctrl
    View-->>Pengguna: Tampilkan tabel laporan dan tombol hijau "EXPORT EXCEL"

    %% Fase 2: Export
    Pengguna->>View: Klik tombol "EXPORT EXCEL"
    View->>Ctrl: GET /account/laporan_debit/export?tanggal_awal=...&tanggal_akhir=...
    activate Ctrl
    Ctrl->>Export: new DebitExport(tanggal_awal, tanggal_akhir)
    Ctrl->>ExcelLib: Excel::download(DebitExport, "laporan-uang-masuk-...xlsx")
    activate ExcelLib
    ExcelLib->>Export: panggil view()
    activate Export
    Export->>DB: Query semua transaksi periode aktif milik user
    DB-->>Export: Return Collection transaksi debit
    Export-->>ExcelLib: Render template Blade excel.blade.php
    deactivate Export
    ExcelLib->>ExcelLib: Konversi tabel HTML Blade ke berkas Spreadsheet (.xlsx)
    ExcelLib-->>Ctrl: Binary File Stream (.xlsx)
    deactivate ExcelLib
    Ctrl-->>Pengguna: Download otomatis file "laporan-uang-masuk-[periode].xlsx" 📥
    deactivate Ctrl
```

---

## 6. Component Diagram (Arsitektur Sistem)
Diagram ini memperlihatkan pemisahan lapisan arsitektur aplikasi (MVC Laravel, Presentation, Service/Export, dan Database).

```mermaid
flowchart TD
    subgraph Client["💻 Client Layer (Browser)"]
        UI["Web Browser (Stisla UI / Bootstrap 4)"]
        JS["Highcharts JS & SweetAlert"]
    end

    subgraph Presentation["🎨 Presentation Layer (Blade Views)"]
        V_Dash["account.dashboard.index"]
        V_Debit["account.debit.*"]
        V_Credit["account.credit.*"]
        V_Stock["account.stock.*"]
        V_LapDebit["account.laporan_debit.*"]
        V_LapCredit["account.laporan_credit.*"]
        V_Excel["excel.blade.php (Template Spreadsheet)"]
    end

    subgraph ControllerLayer["⚙️ Controller Layer"]
        C_Dash["DashboardController"]
        C_Debit["DebitController"]
        C_Credit["CreditController"]
        C_Stock["StockBarangController"]
        C_LapDebit["LaporanDebitController"]
        C_LapCredit["LaporanCreditController"]
    end

    subgraph BusinessLayer["🧠 Business & Export Services"]
        E_Debit["DebitExport (Maatwebsite Excel)"]
        E_Credit["CreditExport (Maatwebsite Excel)"]
        Tx["DB Transaction Manager (Atomic Lock)"]
    end

    subgraph ModelLayer["🏛️ Eloquent Model Layer"]
        M_User["User"]
        M_Debit["Debit"]
        M_Credit["Credit"]
        M_CatDebit["CategoriesDebit"]
        M_CatCredit["CategoriesCredit"]
        M_Stock["StockBarang"]
    end

    subgraph DataLayer["🗄️ Persistence Layer"]
        DB[(MySQL Database)]
    end

    UI --> Presentation
    Presentation --> ControllerLayer
    ControllerLayer --> BusinessLayer
    BusinessLayer --> ModelLayer
    ControllerLayer --> ModelLayer
    ModelLayer --> DataLayer
    BusinessLayer --> V_Excel
```
