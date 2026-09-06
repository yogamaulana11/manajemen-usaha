# Pengurangan Stok Barang Saat Transaksi Uang Masuk Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mengintegrasikan pencatatan Uang Masuk (Debit) dengan Stok Barang (StockBarang), sehingga pengguna dapat memilih barang dan jumlah (qty) yang terjual untuk otomatis memotong sisa stok dengan aman (ACID transaction) serta mengembalikan stok jika data dihapus atau diubah.

**Architecture:** Menambahkan kolom `stock_id` dan `qty` pada tabel `debit`. Di controller `DebitController`, setiap operasi penambahan, perubahan, atau penghapusan dibungkus dalam `DB::transaction()` untuk memvalidasi ketersediaan stok, memotong/menyesuaikan `jumlah_stok` di tabel `stock_barang`, dan memperbarui `tanggal_update`. Di tampilan Blade (create, edit, index), input barang & qty ditambahkan secara elegan menggunakan Select2.

**Tech Stack:** PHP 7.4 / Laravel 6.2, MySQL/MariaDB, Blade Templating, Bootstrap 4 / Stisla, PHPUnit 8.5.

## Global Constraints
- `stock_id` dan `qty` pada `debit` bersifat opsional (nullable); hanya diproses jika `stock_id` dipilih.
- Jika `stock_id` dipilih, `qty` wajib diisi minimal 1 dan tidak boleh melebihi sisa stok yang tersedia.
- Konsistensi stok dijamin dengan database transaction (`DB::transaction`) dan row-level locking (`lockForUpdate`).
- Pengurangan stok harus mengupdate timestamp `tanggal_update` pada tabel `stock_barang`.
- Stok dikembalikan saat transaksi debit dihapus atau diedit.

---

### Task 1: Fix Helper Function Redeclaration Guard

**Files:**
- Modify: `config/helpers.php:1-19`

**Interfaces:**
- Produces: `function setActive($path)` dan `function rupiah($angka)` aman dipanggil berkali-kali dalam testing/runtime tanpa fatal error `Cannot redeclare`.

- [x] **Step 1: Update `config/helpers.php` dengan `function_exists` guard**

```php
<?php
/**
 * Return nav-here if current path begins with this path.
 *
 * @param string $path
 * @return string
 */
if (!function_exists('setActive')) {
    function setActive($path)
    {
        return Request::is($path . '*') ? ' active' : '';
    }
}

if (!function_exists('rupiah')) {
    function rupiah($angka)
    {
        $hasil_rupiah = "Rp. " . number_format($angka, 2, ',', '.');
        return $hasil_rupiah;
    }
}
```

- [x] **Step 2: Jalankan PHPUnit untuk memastikan tidak ada fatal error redeclaration**

Run: `./vendor/bin/phpunit --filter nothing`
Expected: Output PHPUnit berjalan normal tanpa `Fatal error: Cannot redeclare setActive()`.

- [x] **Step 3: Commit perbaikan helper**

```bash
git add config/helpers.php
git commit -m "fix: tambahkan function_exists guard pada helpers untuk test isolation"
```

---

### Task 2: Database Migration Menambahkan `stock_id` dan `qty` ke Tabel `debit`

**Files:**
- Create: `database/migrations/2026_09_06_000000_add_stock_id_and_qty_to_debit_table.php`

**Interfaces:**
- Consumes: Skema tabel `debit` dan `stock_barang` yang sudah ada.
- Produces: Kolom `stock_id` (nullable foreign key) dan `qty` (nullable integer) pada tabel `debit`.

- [x] **Step 1: Buat file migrasi `2026_09_06_000000_add_stock_id_and_qty_to_debit_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStockIdAndQtyToDebitTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('debit', function (Blueprint $table) {
            $table->unsignedBigInteger('stock_id')->nullable()->after('category_id');
            $table->integer('qty')->nullable()->after('stock_id');

            $table->foreign('stock_id')
                ->references('id')->on('stock_barang')
                ->onDelete('SET NULL')
                ->onUpdate('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('debit', function (Blueprint $table) {
            $table->dropForeign(['stock_id']);
            $table->dropColumn(['stock_id', 'qty']);
        });
    }
}
```

- [x] **Step 2: Jalankan migrasi database**

Run: `php artisan migrate`
Expected: `Migrating: 2026_09_06_000000_add_stock_id_and_qty_to_debit_table` -> `Migrated`.

- [x] **Step 3: Commit migrasi**

```bash
git add database/migrations/2026_09_06_000000_add_stock_id_and_qty_to_debit_table.php
git commit -m "feat: migrasi tambahkan stock_id dan qty pada tabel debit"
```

---

### Task 3: Update Model Eloquent `Debit` dan `StockBarang`

**Files:**
- Modify: `app/Debit.php`
- Modify: `app/StockBarang.php`

**Interfaces:**
- Consumes: Skema kolom baru `stock_id` dan `qty`.
- Produces: `$fillable` fields dan relasi `stock()` di `Debit`, serta `debits()` di `StockBarang`.

- [x] **Step 1: Update `app/Debit.php`**

Tambahkan `stock_id` dan `qty` ke `$fillable`, serta method `stock()`:

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Debit extends Model
{
    /**
     * @var string
     */
    protected $table = 'debit';

    /**
     * @var array
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'stock_id',
        'qty',
        'nominal',
        'description',
        'debit_date'
    ];

    public function stock()
    {
        return $this->belongsTo(StockBarang::class, 'stock_id');
    }

    public function category()
    {
        return $this->belongsTo(CategoriesDebit::class, 'category_id');
    }
}
```

- [x] **Step 2: Update `app/StockBarang.php`**

Tambahkan relasi `debits()`:

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StockBarang extends Model
{
    /**
     * @var string
     */
    protected $table = 'stock_barang';

    /**
     * @var array
     */
    protected $fillable = [
        'user_id',
        'kategori_barang',
        'nama_barang',
        'jumlah_stok',
        'tanggal_update'
    ];

    public function debits()
    {
        return $this->hasMany(Debit::class, 'stock_id');
    }
}
```

- [x] **Step 3: Verifikasi sintaks PHP**

Run: `php -l app/Debit.php && php -l app/StockBarang.php`
Expected: `No syntax errors detected`

- [x] **Step 4: Commit pembaruan model**

```bash
git add app/Debit.php app/StockBarang.php
git commit -m "feat: tambahkan relasi dan fillable stock_id dan qty pada model Debit dan StockBarang"
```

---

### Task 4: Implementasi Logika Pengurangan & Pengembalian Stok di `DebitController`

**Files:**
- Modify: `app/Http/Controllers/account/DebitController.php`

**Interfaces:**
- Consumes: Request form `stock_id`, `qty`, `nominal`, `debit_date`, `category_id`, `description`.
- Produces: CRUD `Debit` yang secara atomic memotong atau mengembalikan stok barang di tabel `stock_barang`.

- [x] **Step 1: Modifikasi `DebitController.php`**

Detail perubahan pada methods:
1. `index()` dan `search()`:
   - Tambahkan join / select `stock_barang.nama_barang` dan `debit.qty`.
2. `create()`:
   - Ambil `$stocks = StockBarang::where('user_id', Auth::user()->id)->where('jumlah_stok', '>', 0)->orderBy('nama_barang', 'ASC')->get();`
   - Kirim compact `categories` dan `stocks`.
3. `store(Request $request)`:
   - Validasi `stock_id` (nullable|exists:stock_barang,id) dan `qty` (required_with:stock_id|nullable|integer|min:1).
   - Gunakan `DB::transaction()`:
     - Jika ada `stock_id`:
       - Kunci row barang: `$stock = StockBarang::where('id', $request->stock_id)->where('user_id', Auth::user()->id)->lockForUpdate()->first();`
       - Jika `!$stock || $stock->jumlah_stok < $request->qty`, return redirect back with error `"Stok barang tidak mencukupi! Sisa stok: " . ($stock ? $stock->jumlah_stok : 0)`.
       - Kurangi stok: `$stock->decrement('jumlah_stok', $request->qty);`
       - Update tanggal update: `$stock->update(['tanggal_update' => now()]);`
     - Simpan data `Debit`.
4. `edit(Request $request, Debit $debit)`:
   - Ambil `$stocks = StockBarang::where('user_id', Auth::user()->id)->orderBy('nama_barang', 'ASC')->get();`
   - Kirim compact `debit`, `categories`, `stocks`.
5. `update(Request $request, Debit $debit)`:
   - Gunakan `DB::transaction()`:
     - Kembalikan stok lama jika transaksi sebelumnya memiliki `stock_id` dan `qty`:
       `if ($debit->stock_id && $debit->qty) { StockBarang::where('id', $debit->stock_id)->increment('jumlah_stok', $debit->qty); }`
     - Jika update memilih `stock_id`:
       - Ambil barang baru dengan `lockForUpdate()`:
       - Cek ketersediaan stok: jika `jumlah_stok < $request->qty`, rollback transaksi dan kembalikan pesan error stok tidak mencukupi.
       - Potong stok: `$stock->decrement('jumlah_stok', $request->qty);`
       - `$stock->update(['tanggal_update' => now()]);`
     - Update record `Debit`.
6. `destroy($id)`:
   - Gunakan `DB::transaction()`:
     - Ambil data debit.
     - Jika ada `stock_id` dan `qty`: kembalikan stok `$stock->increment('jumlah_stok', $debit->qty)`.
     - Hapus debit.

- [x] **Step 2: Verifikasi sintaks PHP**

Run: `php -l app/Http/Controllers/account/DebitController.php`
Expected: `No syntax errors detected`

- [x] **Step 3: Commit controller**

```bash
git add app/Http/Controllers/account/DebitController.php
git commit -m "feat: tambahkan logika pengurangan dan pengembalian stok pada DebitController"
```

---

### Task 5: Update Tampilan Blade (`create.blade.php`, `edit.blade.php`, `index.blade.php`)

**Files:**
- Modify: `resources/views/account/debit/create.blade.php`
- Modify: `resources/views/account/debit/edit.blade.php`
- Modify: `resources/views/account/debit/index.blade.php`

**Interfaces:**
- Consumes: `$stocks` pada view create/edit, `$item->nama_barang` dan `$item->qty` pada view index.
- Produces: Input dropdown barang & jumlah (qty) yang rapi dan konsisten dengan styling Stisla.

- [x] **Step 1: Update `resources/views/account/debit/create.blade.php`**

Tambahkan baris pilihan barang dan jumlah stok setelah kategori:
```html
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>BARANG (OPSIONAL)</label>
            <select class="form-control select2" name="stock_id" id="stock_id" style="width: 100%">
                <option value="">-- PILIH BARANG (OPSIONAL) --</option>
                @foreach ($stocks as $stock)
                    <option value="{{ $stock->id }}" {{ old('stock_id') == $stock->id ? 'selected' : '' }}>
                        {{ $stock->nama_barang }} (Sisa Stok: {{ $stock->jumlah_stok }})
                    </option>
                @endforeach
            </select>
            @error('stock_id')
            <div class="invalid-feedback" style="display: block">
                {{ $message }}
            </div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>JUMLAH (QTY)</label>
            <input type="number" name="qty" id="qty" value="{{ old('qty') }}" min="1" placeholder="Masukkan Jumlah Barang" class="form-control">
            @error('qty')
            <div class="invalid-feedback" style="display: block">
                {{ $message }}
            </div>
            @enderror
        </div>
    </div>
</div>
```

- [x] **Step 2: Update `resources/views/account/debit/edit.blade.php`**

Tambahkan baris input `stock_id` dan `qty` dengan nilai existing dari `$debit->stock_id` dan `$debit->qty`.

- [x] **Step 3: Update `resources/views/account/debit/index.blade.php`**

Tambahkan kolom atau keterangan barang yang terjual: jika record debit memiliki `nama_barang`, tampilkan badge:
`<span class="badge badge-info mt-1"><i class="fas fa-box"></i> {{ $hasil->nama_barang }} ({{ $hasil->qty }} pcs)</span>`

- [x] **Step 4: Commit perubahan views**

```bash
git add resources/views/account/debit/
git commit -m "feat: tambahkan input barang & qty pada view create, edit, dan tampilan index debit"
```

---

### Task 6: Pengujian & Verifikasi Terintegrasi

**Files:**
- Create: `tests/Feature/DebitStockTest.php`

**Interfaces:**
- Consumes: HTTP endpoints `/account/debit`, `/account/debit/store`, `/account/debit/{id}`, `/account/stock`.
- Produces: Test assertions otomatis yang memverifikasi pengurangan stok saat simpan, penolakan saat stok kurang, dan pengembalian stok saat hapus/edit.

- [x] **Step 1: Buat test suite `tests/Feature/DebitStockTest.php`**

```php
<?php

namespace Tests\Feature;

use App\CategoriesDebit;
use App\Debit;
use App\StockBarang;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DebitStockTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $category;
    protected $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->category = CategoriesDebit::create([
            'user_id' => $this->user->id,
            'name' => 'Penjualan Makanan'
        ]);
        $this->stock = StockBarang::create([
            'user_id' => $this->user->id,
            'kategori_barang' => 'Pempek',
            'nama_barang' => 'Pempek Kapal Selam',
            'jumlah_stok' => 20,
            'tanggal_update' => now()
        ]);
    }

    public function test_stok_berkurang_saat_uang_masuk_disimpan()
    {
        $response = $this->actingAs($this->user)->post(route('account.debit.store'), [
            'nominal' => '50,000',
            'debit_date' => now()->format('Y-m-d H:i:s'),
            'category_id' => $this->category->id,
            'description' => 'Penjualan 5 pcs Pempek',
            'stock_id' => $this->stock->id,
            'qty' => 5
        ]);

        $response->assertRedirect(route('account.debit.index'));
        $this->assertDatabaseHas('debit', [
            'user_id' => $this->user->id,
            'stock_id' => $this->stock->id,
            'qty' => 5
        ]);

        $this->assertEquals(15, $this->stock->fresh()->jumlah_stok);
    }

    public function test_transaksi_ditolak_jika_qty_melebihi_stok()
    {
        $response = $this->actingAs($this->user)->from(route('account.debit.create'))->post(route('account.debit.store'), [
            'nominal' => '500,000',
            'debit_date' => now()->format('Y-m-d H:i:s'),
            'category_id' => $this->category->id,
            'description' => 'Penjualan overstock',
            'stock_id' => $this->stock->id,
            'qty' => 50
        ]);

        $response->assertRedirect(route('account.debit.create'));
        $this->assertEquals(20, $this->stock->fresh()->jumlah_stok);
    }

    public function test_stok_dikembalikan_saat_debit_dihapus()
    {
        $debit = Debit::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'stock_id' => $this->stock->id,
            'qty' => 4,
            'nominal' => 40000,
            'description' => 'Test debit',
            'debit_date' => now()
        ]);
        $this->stock->decrement('jumlah_stok', 4);
        $this->assertEquals(16, $this->stock->fresh()->jumlah_stok);

        $response = $this->actingAs($this->user)->delete(route('account.debit.destroy', $debit->id));
        $response->assertJson(['status' => 'success']);

        $this->assertEquals(20, $this->stock->fresh()->jumlah_stok);
    }
}
```

- [x] **Step 2: Jalankan automated test**

Run: `./vendor/bin/phpunit tests/Feature/DebitStockTest.php`
Expected: 3 tests, assertions passed (green).

- [x] **Step 3: Commit test suite**

```bash
git add tests/Feature/DebitStockTest.php
git commit -m "test: tambahkan automated feature test untuk pengurangan dan pengembalian stok saat uang masuk"
```
