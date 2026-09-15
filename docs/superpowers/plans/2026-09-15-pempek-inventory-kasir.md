# Implementasi Sistem Pengelolaan Inventaris & Kasir Pempek

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mengimplementasikan modul Master Data Pempek (`master_pempek`), Transaksi Produksi multi-item (`produksi_header`, `produksi_detail` / Stock In), dan Transaksi Penjualan Kasir multi-item (`penjualan_header`, `penjualan_detail` / Stock Out) lengkap dengan validasi stok, cetak struk nota, dan integrasi otomatis ke pencatatan Uang Masuk (`debit`).

**Architecture:** Menerapkan arsitektur relasional Header-Detail pada Laravel 6.x dengan Eloquent ORM, migrasi database dengan constraint integritas referensial (FK cascade/restrict), mutasi stok atomik menggunakan `DB::transaction()` & `lockForUpdate()`, antarmuka dinamis multi-baris (enter to append row) pada modul produksi, serta antarmuka Point of Sale (POS) interaktif pada kasir penjualan dengan integrasi otomatis ke modul `debit`.

**Tech Stack:** PHP 7.4, Laravel 6.x, MySQL, Blade Template Engine, Bootstrap 4 / Stisla Admin Theme, jQuery & Cleave.js, PHPUnit 8.5.

## Global Constraints
- Primary Key untuk `master_pempek` adalah `kode_pempek` (string VARCHAR(20)).
- Primary Key untuk `produksi_header` adalah `no_faktur` (string VARCHAR(30), format `PRD-YYYYMMDD-XXXX`).
- Primary Key untuk `penjualan_header` adalah `no_faktur` (string VARCHAR(30), format `INV-YYYYMMDD-XXXX`).
- Semua entitas header dan master data diisolasi per pengguna menggunakan `user_id` merujuk ke `users.id`.
- Mutasi stok harus bersifat atomik dalam `DB::transaction()`.
- Penjualan kasir otomatis mencatat entri `debit` untuk user aktif dengan kategori "Penjualan Pempek".

---

### Task 1: Database Migrations & Eloquent Models

**Files:**
- Create: `database/migrations/2026_09_15_000001_create_master_pempek_table.php`
- Create: `database/migrations/2026_09_15_000002_create_produksi_pempek_tables.php`
- Create: `database/migrations/2026_09_15_000003_create_penjualan_pempek_tables.php`
- Create: `app/MasterPempek.php`
- Create: `app/ProduksiHeader.php`
- Create: `app/ProduksiDetail.php`
- Create: `app/PenjualanHeader.php`
- Create: `app/PenjualanDetail.php`
- Test: `tests/Feature/PempekSchemaModelTest.php`

**Interfaces:**
- Produces:
  - `App\MasterPempek` (`kode_pempek`, `user_id`, `nama_pempek`, `jenis_ikan`, `harga`, `foto`, `stok`)
  - `App\ProduksiHeader` (`no_faktur`, `user_id`, `tanggal`, `keterangan`)
  - `App\ProduksiDetail` (`id_detail`, `no_faktur`, `kode_pempek`, `jumlah_produksi`)
  - `App\PenjualanHeader` (`no_faktur`, `user_id`, `tanggal_jual`, `total_bayar`, `bayar`, `kembalian`, `catatan`)
  - `App\PenjualanDetail` (`id_detail`, `no_faktur`, `kode_pempek`, `harga`, `jumlah_jual`, `subtotal`)

- [ ] **Step 1: Write the failing test for schema and models**

Buat file `tests/Feature/PempekSchemaModelTest.php`:
```php
<?php

namespace Tests\Feature;

use App\MasterPempek;
use App\PenjualanDetail;
use App\PenjualanHeader;
use App\ProduksiDetail;
use App\ProduksiHeader;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PempekSchemaModelTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_create_and_relate_pempek_entities()
    {
        $user = factory(User::class)->create();

        $pempek = MasterPempek::create([
            'kode_pempek' => 'PMP-TEST-01',
            'user_id'     => $user->id,
            'nama_pempek' => 'Pempek Lenjer',
            'jenis_ikan'  => 'Tenggiri',
            'harga'       => 15000,
            'stok'        => 10,
        ]);

        $this->assertEquals('PMP-TEST-01', $pempek->kode_pempek);
        $this->assertEquals(15000, $pempek->harga);

        $prodHeader = ProduksiHeader::create([
            'no_faktur'  => 'PRD-TEST-001',
            'user_id'    => $user->id,
            'tanggal'    => now(),
            'keterangan' => 'Produksi Batch 1'
        ]);

        $prodDetail = ProduksiDetail::create([
            'no_faktur'       => $prodHeader->no_faktur,
            'kode_pempek'     => $pempek->kode_pempek,
            'jumlah_produksi' => 25
        ]);

        $this->assertCount(1, $prodHeader->details);
        $this->assertEquals($pempek->kode_pempek, $prodHeader->details->first()->pempek->kode_pempek);

        $penjualanHeader = PenjualanHeader::create([
            'no_faktur'    => 'INV-TEST-001',
            'user_id'      => $user->id,
            'tanggal_jual' => now(),
            'total_bayar'  => 30000,
            'bayar'        => 50000,
            'kembalian'    => 20000,
            'catatan'      => 'Take away'
        ]);

        $penjualanDetail = PenjualanDetail::create([
            'no_faktur'   => $penjualanHeader->no_faktur,
            'kode_pempek' => $pempek->kode_pempek,
            'harga'       => 15000,
            'jumlah_jual' => 2,
            'subtotal'    => 30000
        ]);

        $this->assertCount(1, $penjualanHeader->details);
        $this->assertEquals(30000, $penjualanHeader->details->first()->subtotal);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php ./vendor/bin/phpunit tests/Feature/PempekSchemaModelTest.php`
Expected: FAIL with "Class 'App\MasterPempek' not found" or "Table doesn't exist".

- [ ] **Step 3: Create database migrations and run artisan migrate**

Buat migrasi tabel `master_pempek`:
`database/migrations/2026_09_15_000001_create_master_pempek_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterPempekTable extends Migration
{
    public function up()
    {
        Schema::create('master_pempek', function (Blueprint $table) {
            $table->string('kode_pempek', 20)->primary();
            $table->unsignedBigInteger('user_id');
            $table->string('nama_pempek', 100);
            $table->string('jenis_ikan', 50);
            $table->decimal('harga', 12, 2);
            $table->string('foto', 255)->nullable();
            $table->integer('stok')->default(0);
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });
    }

    public function down()
    {
        Schema::dropIfExists('master_pempek');
    }
}
```

Buat migrasi tabel transaksi produksi:
`database/migrations/2026_09_15_000002_create_produksi_pempek_tables.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProduksiPempekTables extends Migration
{
    public function up()
    {
        Schema::create('produksi_header', function (Blueprint $table) {
            $table->string('no_faktur', 30)->primary();
            $table->unsignedBigInteger('user_id');
            $table->dateTime('tanggal');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });

        Schema::create('produksi_detail', function (Blueprint $table) {
            $table->bigIncrements('id_detail');
            $table->string('no_faktur', 30);
            $table->string('kode_pempek', 20);
            $table->integer('jumlah_produksi');
            $table->timestamps();

            $table->foreign('no_faktur')
                ->references('no_faktur')->on('produksi_header')
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');

            $table->foreign('kode_pempek')
                ->references('kode_pempek')->on('master_pempek')
                ->onDelete('RESTRICT')
                ->onUpdate('CASCADE');
        });
    }

    public function down()
    {
        Schema::dropIfExists('produksi_detail');
        Schema::dropIfExists('produksi_header');
    }
}
```

Buat migrasi tabel transaksi penjualan / kasir:
`database/migrations/2026_09_15_000003_create_penjualan_pempek_tables.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePenjualanPempekTables extends Migration
{
    public function up()
    {
        Schema::create('penjualan_header', function (Blueprint $table) {
            $table->string('no_faktur', 30)->primary();
            $table->unsignedBigInteger('user_id');
            $table->dateTime('tanggal_jual');
            $table->decimal('total_bayar', 12, 2);
            $table->decimal('bayar', 12, 2)->default(0);
            $table->decimal('kembalian', 12, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');
        });

        Schema::create('penjualan_detail', function (Blueprint $table) {
            $table->bigIncrements('id_detail');
            $table->string('no_faktur', 30);
            $table->string('kode_pempek', 20);
            $table->decimal('harga', 12, 2);
            $table->integer('jumlah_jual');
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->foreign('no_faktur')
                ->references('no_faktur')->on('penjualan_header')
                ->onDelete('CASCADE')
                ->onUpdate('CASCADE');

            $table->foreign('kode_pempek')
                ->references('kode_pempek')->on('master_pempek')
                ->onDelete('RESTRICT')
                ->onUpdate('CASCADE');
        });
    }

    public function down()
    {
        Schema::dropIfExists('penjualan_detail');
        Schema::dropIfExists('penjualan_header');
    }
}
```

Jalankan artisan migrate:
`php artisan migrate`

- [ ] **Step 4: Create Eloquent Models**

Buat `app/MasterPempek.php`:
```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MasterPempek extends Model
{
    protected $table = 'master_pempek';
    protected $primaryKey = 'kode_pempek';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'kode_pempek',
        'user_id',
        'nama_pempek',
        'jenis_ikan',
        'harga',
        'foto',
        'stok',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function produksiDetails()
    {
        return $this->hasMany(ProduksiDetail::class, 'kode_pempek', 'kode_pempek');
    }

    public function penjualanDetails()
    {
        return $this->hasMany(PenjualanDetail::class, 'kode_pempek', 'kode_pempek');
    }
}
```

Buat `app/ProduksiHeader.php`:
```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProduksiHeader extends Model
{
    protected $table = 'produksi_header';
    protected $primaryKey = 'no_faktur';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'no_faktur',
        'user_id',
        'tanggal',
        'keterangan',
    ];

    protected $dates = ['tanggal'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details()
    {
        return $this->hasMany(ProduksiDetail::class, 'no_faktur', 'no_faktur');
    }
}
```

Buat `app/ProduksiDetail.php`:
```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProduksiDetail extends Model
{
    protected $table = 'produksi_detail';
    protected $primaryKey = 'id_detail';

    protected $fillable = [
        'no_faktur',
        'kode_pempek',
        'jumlah_produksi',
    ];

    public function header()
    {
        return $this->belongsTo(ProduksiHeader::class, 'no_faktur', 'no_faktur');
    }

    public function pempek()
    {
        return $this->belongsTo(MasterPempek::class, 'kode_pempek', 'kode_pempek');
    }
}
```

Buat `app/PenjualanHeader.php`:
```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PenjualanHeader extends Model
{
    protected $table = 'penjualan_header';
    protected $primaryKey = 'no_faktur';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'no_faktur',
        'user_id',
        'tanggal_jual',
        'total_bayar',
        'bayar',
        'kembalian',
        'catatan',
    ];

    protected $dates = ['tanggal_jual'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details()
    {
        return $this->hasMany(PenjualanDetail::class, 'no_faktur', 'no_faktur');
    }
}
```

Buat `app/PenjualanDetail.php`:
```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PenjualanDetail extends Model
{
    protected $table = 'penjualan_detail';
    protected $primaryKey = 'id_detail';

    protected $fillable = [
        'no_faktur',
        'kode_pempek',
        'harga',
        'jumlah_jual',
        'subtotal',
    ];

    public function header()
    {
        return $this->belongsTo(PenjualanHeader::class, 'no_faktur', 'no_faktur');
    }

    public function pempek()
    {
        return $this->belongsTo(MasterPempek::class, 'kode_pempek', 'kode_pempek');
    }
}
```

- [ ] **Step 5: Run tests and verify they pass**

Run: `php ./vendor/bin/phpunit tests/Feature/PempekSchemaModelTest.php`
Expected: PASS (1 test, 5 assertions).

- [ ] **Step 6: Commit**

```bash
git add database/migrations/ app/MasterPempek.php app/ProduksiHeader.php app/ProduksiDetail.php app/PenjualanHeader.php app/PenjualanDetail.php tests/Feature/PempekSchemaModelTest.php
git commit -m "feat: tambahkan skema database dan model eloquet inventaris kasir pempek"
```

---

### Task 2: Modul Master Data Pempek (CRUD, Foto Upload, Auto Kode)

**Files:**
- Create: `app/Http/Controllers/account/MasterPempekController.php`
- Create: `resources/views/account/master_pempek/index.blade.php`
- Create: `resources/views/account/master_pempek/create.blade.php`
- Create: `resources/views/account/master_pempek/edit.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/MasterPempekTest.php`

**Interfaces:**
- Consumes: `App\MasterPempek`, `App\User`
- Produces: Web routes `account.master_pempek.*` (index, create, store, edit, update, destroy, search)

- [ ] **Step 1: Write the failing test for Master Pempek CRUD**

Buat file `tests/Feature/MasterPempekTest.php`:
```php
<?php

namespace Tests\Feature;

use App\MasterPempek;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MasterPempekTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
    }

    public function test_user_can_view_master_pempek_list()
    {
        MasterPempek::create([
            'kode_pempek' => 'PMP-TEST-001',
            'user_id'     => $this->user->id,
            'nama_pempek' => 'Pempek Adaan',
            'jenis_ikan'  => 'Tenggiri',
            'harga'       => 10000,
            'stok'        => 50
        ]);

        $response = $this->actingAs($this->user)->get(route('account.master_pempek.index'));
        $response->assertStatus(200);
        $response->assertSee('Pempek Adaan');
        $response->assertSee('PMP-TEST-001');
    }

    public function test_user_can_store_master_pempek_with_photo()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('pempek.jpg');

        $response = $this->actingAs($this->user)->post(route('account.master_pempek.store'), [
            'kode_pempek' => 'PMP-TEST-002',
            'nama_pempek' => 'Pempek Kulit',
            'jenis_ikan'  => 'Tenggiri',
            'harga'       => '12.000',
            'foto'        => $file,
        ]);

        $response->assertRedirect(route('account.master_pempek.index'));
        $this->assertDatabaseHas('master_pempek', [
            'kode_pempek' => 'PMP-TEST-002',
            'user_id'     => $this->user->id,
            'nama_pempek' => 'Pempek Kulit',
            'harga'       => 12000,
            'stok'        => 0
        ]);
    }

    public function test_user_can_update_master_pempek()
    {
        $pempek = MasterPempek::create([
            'kode_pempek' => 'PMP-TEST-003',
            'user_id'     => $this->user->id,
            'nama_pempek' => 'Pempek Pistel',
            'jenis_ikan'  => 'Gabus',
            'harga'       => 8000,
            'stok'        => 10
        ]);

        $response = $this->actingAs($this->user)->put(route('account.master_pempek.update', $pempek->kode_pempek), [
            'nama_pempek' => 'Pempek Pistel Spesial',
            'jenis_ikan'  => 'Kakap',
            'harga'       => '9.500',
        ]);

        $response->assertRedirect(route('account.master_pempek.index'));
        $this->assertDatabaseHas('master_pempek', [
            'kode_pempek' => 'PMP-TEST-003',
            'nama_pempek' => 'Pempek Pistel Spesial',
            'harga'       => 9500
        ]);
    }

    public function test_user_can_delete_master_pempek()
    {
        $pempek = MasterPempek::create([
            'kode_pempek' => 'PMP-TEST-004',
            'user_id'     => $this->user->id,
            'nama_pempek' => 'Pempek Tahu',
            'jenis_ikan'  => 'Tenggiri',
            'harga'       => 7000,
            'stok'        => 0
        ]);

        $response = $this->actingAs($this->user)->delete(route('account.master_pempek.destroy', $pempek->kode_pempek));
        $response->assertStatus(200);
        $this->assertDatabaseMissing('master_pempek', [
            'kode_pempek' => 'PMP-TEST-004'
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php ./vendor/bin/phpunit tests/Feature/MasterPempekTest.php`
Expected: FAIL with "Route [account.master_pempek.index] not defined".

- [ ] **Step 3: Implement MasterPempekController and register routes in routes/web.php**

Modifikasi `routes/web.php` di dalam `Route::prefix('account')->group(...)`:
```php
    // master pempek
    Route::get('/master_pempek/search', 'account\MasterPempekController@search')->name('account.master_pempek.search');
    Route::Resource('/master_pempek', 'account\MasterPempekController', ['as' => 'account']);
```

Buat `app/Http/Controllers/account/MasterPempekController.php`:
```php
<?php

namespace App\Http\Controllers\account;

use App\Http\Controllers\Controller;
use App\MasterPempek;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MasterPempekController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $pempek = MasterPempek::where('user_id', Auth::id())
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return view('account.master_pempek.index', compact('pempek'));
    }

    public function search(Request $request)
    {
        $search = $request->get('q');
        $pempek = MasterPempek::where('user_id', Auth::id())
            ->where(function ($query) use ($search) {
                $query->where('kode_pempek', 'LIKE', '%' . $search . '%')
                    ->orWhere('nama_pempek', 'LIKE', '%' . $search . '%')
                    ->orWhere('jenis_ikan', 'LIKE', '%' . $search . '%');
            })
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return view('account.master_pempek.index', compact('pempek'));
    }

    public function create()
    {
        // Generate auto next code
        $count = MasterPempek::where('user_id', Auth::id())->count() + 1;
        $autoCode = 'PMP-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        while (MasterPempek::where('kode_pempek', $autoCode)->exists()) {
            $count++;
            $autoCode = 'PMP-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        }

        return view('account.master_pempek.create', compact('autoCode'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'kode_pempek' => 'required|string|max:20|unique:master_pempek,kode_pempek',
            'nama_pempek' => 'required|string|max:100',
            'jenis_ikan'  => 'required|string|max:50',
            'harga'       => 'required',
            'foto'        => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
        ], [
            'kode_pempek.unique' => 'Kode pempek ini sudah digunakan!',
        ]);

        $cleanHarga = str_replace([',', '.'], '', $request->input('harga'));

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $image = $request->file('foto');
            $fotoPath = $image->store('public/pempek');
            $fotoPath = basename($fotoPath);
        }

        MasterPempek::create([
            'kode_pempek' => strtoupper($request->input('kode_pempek')),
            'user_id'     => Auth::id(),
            'nama_pempek' => $request->input('nama_pempek'),
            'jenis_ikan'  => $request->input('jenis_ikan'),
            'harga'       => $cleanHarga,
            'foto'        => $fotoPath,
            'stok'        => 0,
        ]);

        return redirect()->route('account.master_pempek.index')->with('success', 'Data Pempek Berhasil Ditambahkan!');
    }

    public function edit($kode_pempek)
    {
        $pempek = MasterPempek::where('kode_pempek', $kode_pempek)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return view('account.master_pempek.edit', compact('pempek'));
    }

    public function update(Request $request, $kode_pempek)
    {
        $pempek = MasterPempek::where('kode_pempek', $kode_pempek)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $this->validate($request, [
            'nama_pempek' => 'required|string|max:100',
            'jenis_ikan'  => 'required|string|max:50',
            'harga'       => 'required',
            'foto'        => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        $cleanHarga = str_replace([',', '.'], '', $request->input('harga'));

        $fotoPath = $pempek->foto;
        if ($request->hasFile('foto')) {
            if ($pempek->foto && Storage::exists('public/pempek/' . $pempek->foto)) {
                Storage::delete('public/pempek/' . $pempek->foto);
            }
            $image = $request->file('foto');
            $uploaded = $image->store('public/pempek');
            $fotoPath = basename($uploaded);
        }

        $pempek->update([
            'nama_pempek' => $request->input('nama_pempek'),
            'jenis_ikan'  => $request->input('jenis_ikan'),
            'harga'       => $cleanHarga,
            'foto'        => $fotoPath,
        ]);

        return redirect()->route('account.master_pempek.index')->with('success', 'Data Pempek Berhasil Diperbarui!');
    }

    public function destroy($kode_pempek)
    {
        $pempek = MasterPempek::where('kode_pempek', $kode_pempek)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($pempek->produksiDetails()->exists() || $pempek->penjualanDetails()->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Produk ini tidak dapat dihapus karena memiliki riwayat transaksi!'
            ], 422);
        }

        if ($pempek->foto && Storage::exists('public/pempek/' . $pempek->foto)) {
            Storage::delete('public/pempek/' . $pempek->foto);
        }

        $pempek->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Data Pempek Berhasil Dihapus!'
        ]);
    }
}
```

- [ ] **Step 4: Create Views for Master Pempek (index, create, edit)**

Buat `resources/views/account/master_pempek/index.blade.php`, `resources/views/account/master_pempek/create.blade.php`, dan `resources/views/account/master_pempek/edit.blade.php`.
Views harus menggunakan template `@extends('layouts.account')`, menampilkan badges stok (hijau jika stok > 5, kuning jika 1-5, merah jika 0), foto preview, format Rupiah, dan tombol aksi Edit & SweetAlert Delete.

- [ ] **Step 5: Run tests and verify they pass**

Run: `php ./vendor/bin/phpunit tests/Feature/MasterPempekTest.php`
Expected: PASS (4 tests, 9 assertions).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/account/MasterPempekController.php resources/views/account/master_pempek/ routes/web.php tests/Feature/MasterPempekTest.php
git commit -m "feat: tambahkan modul master data pempek lengkap dengan validasi dan upload foto"
```

---

### Task 3: Modul Transaksi Produksi (Stock In Multi-Item)

**Files:**
- Create: `app/Http/Controllers/account/ProduksiPempekController.php`
- Create: `resources/views/account/produksi/index.blade.php`
- Create: `resources/views/account/produksi/create.blade.php`
- Create: `resources/views/account/produksi/detail.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ProduksiPempekTest.php`

**Interfaces:**
- Consumes: `App\MasterPempek`, `App\ProduksiHeader`, `App\ProduksiDetail`
- Produces: Web routes `account.produksi.*` (index, create, store, show, search)

- [ ] **Step 1: Write the failing test for Produksi Multi-Item Stock In**

Buat file `tests/Feature/ProduksiPempekTest.php`:
```php
<?php

namespace Tests\Feature;

use App\MasterPempek;
use App\ProduksiHeader;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProduksiPempekTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $p1;
    protected $p2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();

        $this->p1 = MasterPempek::create([
            'kode_pempek' => 'PMP-PRD-01',
            'user_id'     => $this->user->id,
            'nama_pempek' => 'Pempek Lenjer',
            'jenis_ikan'  => 'Tenggiri',
            'harga'       => 15000,
            'stok'        => 10,
        ]);

        $this->p2 = MasterPempek::create([
            'kode_pempek' => 'PMP-PRD-02',
            'user_id'     => $this->user->id,
            'nama_pempek' => 'Pempek Kapal Selam',
            'jenis_ikan'  => 'Tenggiri',
            'harga'       => 20000,
            'stok'        => 5,
        ]);
    }

    public function test_can_record_multi_item_production_and_increase_stock()
    {
        $response = $this->actingAs($this->user)->post(route('account.produksi.store'), [
            'tanggal'    => now()->format('Y-m-d H:i:s'),
            'keterangan' => 'Produksi Pagi',
            'items'      => [
                [
                    'kode_pempek'     => $this->p1->kode_pempek,
                    'jumlah_produksi' => 20
                ],
                [
                    'kode_pempek'     => $this->p2->kode_pempek,
                    'jumlah_produksi' => 15
                ]
            ]
        ]);

        $response->assertRedirect(route('account.produksi.index'));

        // Pastikan stok bertambah
        $this->assertEquals(30, $this->p1->fresh()->stok);
        $this->assertEquals(20, $this->p2->fresh()->stok);

        $this->assertDatabaseHas('produksi_header', [
            'user_id'    => $this->user->id,
            'keterangan' => 'Produksi Pagi'
        ]);

        $this->assertDatabaseHas('produksi_detail', [
            'kode_pempek'     => $this->p1->kode_pempek,
            'jumlah_produksi' => 20
        ]);
    }

    public function test_production_fails_without_items()
    {
        $response = $this->actingAs($this->user)->post(route('account.produksi.store'), [
            'tanggal' => now()->format('Y-m-d H:i:s'),
            'items'   => []
        ]);

        $response->assertSessionHasErrors(['items']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php ./vendor/bin/phpunit tests/Feature/ProduksiPempekTest.php`
Expected: FAIL with "Route [account.produksi.store] not defined".

- [ ] **Step 3: Implement ProduksiPempekController and register routes**

Modifikasi `routes/web.php`:
```php
    // produksi pempek (stock in)
    Route::get('/produksi/search', 'account\ProduksiPempekController@search')->name('account.produksi.search');
    Route::get('/produksi', 'account\ProduksiPempekController@index')->name('account.produksi.index');
    Route::get('/produksi/create', 'account\ProduksiPempekController@create')->name('account.produksi.create');
    Route::post('/produksi', 'account\ProduksiPempekController@store')->name('account.produksi.store');
    Route::get('/produksi/{no_faktur}', 'account\ProduksiPempekController@show')->name('account.produksi.show');
```

Buat `app/Http/Controllers/account/ProduksiPempekController.php`:
Mengimplementasikan:
- Generate nomor faktur: `PRD-YYYYMMDD-XXXX`.
- `DB::transaction()` untuk insert header, insert detail, dan `lockForUpdate()->increment('stok', $qty)`.
- Method `create()` mengirimkan daftar produk pempek milik user aktif.
- Method `show()` menampilkan rincian barang per faktur.

- [ ] **Step 4: Create Views for Produksi (index, create, detail)**

Buat `resources/views/account/produksi/create.blade.php`:
- Mengimplementasikan baris input dinamis:
  - Dropdown `kode_pempek` & input `jumlah_produksi`.
  - Tombol Tambah / event `keydown Enter` pada input kuantitas untuk langsung menambahkan baris ke tabel HTML dinamis (dengan hidden inputs `items[index][kode_pempek]` dan `items[index][jumlah_produksi]`).
  - Tombol hapus baris dinamis.
  - Ringkasan total unit produksi.

- [ ] **Step 5: Run tests and verify they pass**

Run: `php ./vendor/bin/phpunit tests/Feature/ProduksiPempekTest.php`
Expected: PASS (2 tests, 6 assertions).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/account/ProduksiPempekController.php resources/views/account/produksi/ routes/web.php tests/Feature/ProduksiPempekTest.php
git commit -m "feat: tambahkan modul produksi multi-item stock in dengan pergerakan stok otomatis"
```

---

### Task 4: Modul Kasir Penjualan (Stock Out, POS, Struk & Debit Integration)

**Files:**
- Create: `app/Http/Controllers/account/PenjualanKasirController.php`
- Create: `resources/views/account/penjualan/index.blade.php`
- Create: `resources/views/account/penjualan/create.blade.php`
- Create: `resources/views/account/penjualan/detail.blade.php`
- Create: `resources/views/account/penjualan/struk.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/PenjualanKasirTest.php`

**Interfaces:**
- Consumes: `App\MasterPempek`, `App\PenjualanHeader`, `App\PenjualanDetail`, `App\Debit`, `App\CategoriesDebit`
- Produces: Web routes `account.penjualan.*` (index, create, store, show, struk, search)

- [ ] **Step 1: Write the failing test for Kasir Penjualan (Stock Out & Debit Sync)**

Buat file `tests/Feature/PenjualanKasirTest.php`:
```php
<?php

namespace Tests\Feature;

use App\Debit;
use App\MasterPempek;
use App\PenjualanHeader;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PenjualanKasirTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $pempek1;
    protected $pempek2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();

        $this->pempek1 = MasterPempek::create([
            'kode_pempek' => 'PMP-POS-01',
            'user_id'     => $this->user->id,
            'nama_pempek' => 'Pempek Lenjer',
            'jenis_ikan'  => 'Tenggiri',
            'harga'       => 10000,
            'stok'        => 20,
        ]);

        $this->pempek2 = MasterPempek::create([
            'kode_pempek' => 'PMP-POS-02',
            'user_id'     => $this->user->id,
            'nama_pempek' => 'Pempek Selam',
            'jenis_ikan'  => 'Tenggiri',
            'harga'       => 20000,
            'stok'        => 10,
        ]);
    }

    public function test_sale_decreases_stock_and_creates_debit_record()
    {
        $response = $this->actingAs($this->user)->post(route('account.penjualan.store'), [
            'tanggal_jual' => now()->format('Y-m-d H:i:s'),
            'bayar'        => '100.000',
            'catatan'      => 'Pesanan meja 3',
            'items'        => [
                [
                    'kode_pempek' => $this->pempek1->kode_pempek,
                    'jumlah_jual' => 3
                ],
                [
                    'kode_pempek' => $this->pempek2->kode_pempek,
                    'jumlah_jual' => 2
                ]
            ]
        ]);

        $response->assertStatus(200); // mengembalikan JSON response dengan URL struk
        $data = $response->json();
        $this->assertEquals('success', $data['status']);

        // Cek pengurangan stok: lenjer (20 - 3 = 17), selam (10 - 2 = 8)
        $this->assertEquals(17, $this->pempek1->fresh()->stok);
        $this->assertEquals(8, $this->pempek2->fresh()->stok);

        // Total: (3 * 10000) + (2 * 20000) = 70000
        $faktur = $data['no_faktur'];
        $this->assertDatabaseHas('penjualan_header', [
            'no_faktur'   => $faktur,
            'user_id'     => $this->user->id,
            'total_bayar' => 70000,
            'bayar'       => 100000,
            'kembalian'   => 30000
        ]);

        // Pastikan terintegrasi ke tabel debit
        $this->assertDatabaseHas('debit', [
            'user_id' => $this->user->id,
            'nominal' => 70000
        ]);
    }

    public function test_sale_fails_if_quantity_exceeds_stock()
    {
        $response = $this->actingAs($this->user)->post(route('account.penjualan.store'), [
            'tanggal_jual' => now()->format('Y-m-d H:i:s'),
            'bayar'        => '500.000',
            'items'        => [
                [
                    'kode_pempek' => $this->pempek1->kode_pempek,
                    'jumlah_jual' => 25 // Stok hanya 20!
                ]
            ]
        ]);

        $response->assertStatus(422);
        // Stok tidak berubah
        $this->assertEquals(20, $this->pempek1->fresh()->stok);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php ./vendor/bin/phpunit tests/Feature/PenjualanKasirTest.php`
Expected: FAIL with "Route [account.penjualan.store] not defined".

- [ ] **Step 3: Implement PenjualanKasirController and register routes**

Modifikasi `routes/web.php`:
```php
    // kasir penjualan (stock out)
    Route::get('/penjualan/search', 'account\PenjualanKasirController@search')->name('account.penjualan.search');
    Route::get('/penjualan', 'account\PenjualanKasirController@index')->name('account.penjualan.index');
    Route::get('/penjualan/create', 'account\PenjualanKasirController@create')->name('account.penjualan.create');
    Route::post('/penjualan', 'account\PenjualanKasirController@store')->name('account.penjualan.store');
    Route::get('/penjualan/{no_faktur}', 'account\PenjualanKasirController@show')->name('account.penjualan.show');
    Route::get('/penjualan/{no_faktur}/struk', 'account\PenjualanKasirController@struk')->name('account.penjualan.struk');
```

Buat `app/Http/Controllers/account/PenjualanKasirController.php`:
Mengimplementasikan:
- Generate nomor faktur `INV-YYYYMMDD-XXXX`.
- Validasi stok server-side: loop item, cek ketersediaan stok; lempar 422 jika kuantitas > stok.
- `DB::transaction()`:
  - Header insert (total, bayar, kembalian).
  - Detail insert dengan harga server-side.
  - Mutasi stok: `lockForUpdate()->decrement('stok', $qty)`.
  - Integrasi debit: mencari `CategoriesDebit` dengan nama "Penjualan Pempek" (atau membuat otomatis jika belum ada), lalu insert ke `Debit`.
- Method `struk($no_faktur)` merender tampilan struk nota kasir thermal 58mm/80mm siap print.

- [ ] **Step 4: Create Views for Penjualan Kasir (index, create, detail, struk)**

Buat `resources/views/account/penjualan/create.blade.php`:
- Antarmuka POS interaktif:
  - Pencarian cepat pempek / dropdown pilih barang.
  - Tampilkan stok terkini & harga satuan.
  - Tambahkan ke keranjang, atur kuantitas (tidak boleh melebihi stok).
  - Tampilan realtime Subtotal, Total Belanja, Input Uang Bayar, dan Kembalian.
  - Submit transaksi via AJAX / Form POST.
  - Tampilkan popup sukses dengan tombol "Cetak Struk" (membuka `/account/penjualan/{no_faktur}/struk` di window baru untuk auto print).
Buat `resources/views/account/penjualan/struk.blade.php`:
- Tampilan rapi format struk thermal dengan tombol Cetak & script auto `window.print()`.

- [ ] **Step 5: Run tests and verify they pass**

Run: `php ./vendor/bin/phpunit tests/Feature/PenjualanKasirTest.php`
Expected: PASS (2 tests, 7 assertions).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/account/PenjualanKasirController.php resources/views/account/penjualan/ routes/web.php tests/Feature/PenjualanKasirTest.php
git commit -m "feat: tambahkan modul kasir penjualan pempek, pemotongan stok otomatis, cetak struk, dan integrasi debit"
```

---

### Task 5: Sidebar Navigation & End-to-End Test

**Files:**
- Modify: `resources/views/layouts/account.blade.php`
- Test: `tests/Feature/PempekNavigationTest.php`

**Interfaces:**
- Consumes: All routes from Task 2, Task 3, Task 4
- Produces: Updated sidebar UI with active state highlights

- [ ] **Step 1: Write the failing test for sidebar navigation**

Buat file `tests/Feature/PempekNavigationTest.php`:
```php
<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PempekNavigationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sidebar_has_pempek_menu_links()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->get(route('account.dashboard.index'));
        $response->assertStatus(200);
        $response->assertSee('MANAJEMEN PEMPEK');
        $response->assertSee(route('account.master_pempek.index'));
        $response->assertSee(route('account.produksi.index'));
        $response->assertSee(route('account.penjualan.index'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php ./vendor/bin/phpunit tests/Feature/PempekNavigationTest.php`
Expected: FAIL with "Failed asserting that ... contains 'MANAJEMEN PEMPEK'".

- [ ] **Step 3: Update sidebar navigation in layouts/account.blade.php**

Modifikasi `resources/views/layouts/account.blade.php` untuk menambahkan menu `MANAJEMEN PEMPEK`:
```blade
<li class="menu-header">MANAJEMEN PEMPEK</li>
<li class="{{ setActive('account/master_pempek') }}"><a class="nav-link"
        href="{{ route('account.master_pempek.index') }}"><i class="fas fa-utensils"></i>
        <span>MASTER PEMPEK</span></a>
</li>
<li class="{{ setActive('account/produksi') }}"><a class="nav-link"
        href="{{ route('account.produksi.index') }}"><i class="fas fa-boxes"></i>
        <span>PRODUKSI (STOCK IN)</span></a>
</li>
<li class="{{ setActive('account/penjualan') }}"><a class="nav-link"
        href="{{ route('account.penjualan.index') }}"><i class="fas fa-cash-register"></i>
        <span>KASIR (STOCK OUT)</span></a>
</li>
```

- [ ] **Step 4: Run test suite to verify everything passes**

Run: `php ./vendor/bin/phpunit tests/Feature/PempekNavigationTest.php`
Expected: PASS (1 test, 5 assertions).

Run full test suite: `php ./vendor/bin/phpunit`
Expected: All tests PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/layouts/account.blade.php tests/Feature/PempekNavigationTest.php
git commit -m "feat: tambahkan navigasi sidebar manajemen pempek dan pengujian tampilan"
```
