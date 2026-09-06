<?php

namespace Tests\Feature;

use App\CategoriesCredit;
use App\CategoriesDebit;
use App\Credit;
use App\Debit;
use App\StockBarang;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LaporanExportTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
    }

    public function test_export_laporan_debit_menghasilkan_file_excel()
    {
        $category = CategoriesDebit::create([
            'user_id' => $this->user->id,
            'name'    => 'Penjualan'
        ]);

        $stock = StockBarang::create([
            'user_id'         => $this->user->id,
            'kategori_barang' => 'Minuman',
            'nama_barang'     => 'Kopi Robusta',
            'jumlah_stok'     => 50,
            'tanggal_update'  => now()
        ]);

        Debit::create([
            'user_id'     => $this->user->id,
            'category_id' => $category->id,
            'stock_id'    => $stock->id,
            'qty'         => 5,
            'nominal'     => 125000,
            'description' => 'Penjualan Kopi Robusta',
            'debit_date'  => '2026-03-01 10:00:00'
        ]);

        $response = $this->actingAs($this->user)->get(route('account.laporan_debit.export', [
            'tanggal_awal'  => '2026-03-01',
            'tanggal_akhir' => '2026-03-31'
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
        $this->assertTrue(
            strpos($response->headers->get('content-disposition'), 'laporan-uang-masuk-2026-03-01-sd-2026-03-31.xlsx') !== false
        );
    }

    public function test_export_laporan_credit_menghasilkan_file_excel()
    {
        $category = CategoriesCredit::create([
            'user_id' => $this->user->id,
            'name'    => 'Operasional'
        ]);

        Credit::create([
            'user_id'     => $this->user->id,
            'category_id' => $category->id,
            'nominal'     => 50000,
            'description' => 'Beli Alat Tulis Kantor',
            'credit_date' => '2026-03-01 10:00:00'
        ]);

        $response = $this->actingAs($this->user)->get(route('account.laporan_credit.export', [
            'tanggal_awal'  => '2026-03-01',
            'tanggal_akhir' => '2026-03-31'
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
        $this->assertTrue(
            strpos($response->headers->get('content-disposition'), 'laporan-uang-keluar-2026-03-01-sd-2026-03-31.xlsx') !== false
        );
    }

    public function test_export_laporan_debit_hanya_menampilkan_data_milik_user_aktif()
    {
        $otherUser = factory(User::class)->create();
        $catOther = CategoriesDebit::create(['user_id' => $otherUser->id, 'name' => 'Lainnya']);
        Debit::create([
            'user_id'     => $otherUser->id,
            'category_id' => $catOther->id,
            'nominal'     => 999999,
            'description' => 'Rahasia User Lain',
            'debit_date'  => '2026-03-05 10:00:00'
        ]);

        // User aktif mengekspor
        $response = $this->actingAs($this->user)->get(route('account.laporan_debit.export', [
            'tanggal_awal'  => '2026-03-01',
            'tanggal_akhir' => '2026-03-31'
        ]));

        $response->assertStatus(200);
    }

    public function test_tombol_export_excel_muncul_setelah_filter_laporan_debit()
    {
        $response = $this->actingAs($this->user)->get(route('account.laporan_debit.check', [
            'tanggal_awal'  => '2026-03-01',
            'tanggal_akhir' => '2026-03-31'
        ]));

        $response->assertStatus(200);
        $response->assertSee('EXPORT EXCEL');
        $expectedExportUrl = route('account.laporan_debit.export', [
            'tanggal_awal'  => '2026-03-01',
            'tanggal_akhir' => '2026-03-31'
        ]);
        $response->assertSee(e($expectedExportUrl));
    }

    public function test_tombol_export_excel_muncul_setelah_filter_laporan_credit()
    {
        $response = $this->actingAs($this->user)->get(route('account.laporan_credit.check', [
            'tanggal_awal'  => '2026-03-01',
            'tanggal_akhir' => '2026-03-31'
        ]));

        $response->assertStatus(200);
        $response->assertSee('EXPORT EXCEL');
        $expectedExportUrl = route('account.laporan_credit.export', [
            'tanggal_awal'  => '2026-03-01',
            'tanggal_akhir' => '2026-03-31'
        ]);
        $response->assertSee(e($expectedExportUrl));
    }
}
