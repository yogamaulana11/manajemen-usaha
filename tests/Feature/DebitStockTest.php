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

    /**
     * @var User
     */
    protected $user;

    /**
     * @var CategoriesDebit
     */
    protected $category;

    /**
     * @var StockBarang
     */
    protected $stock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create();

        $this->category = CategoriesDebit::create([
            'user_id' => $this->user->id,
            'name'    => 'Penjualan Pempek'
        ]);

        $this->stock = StockBarang::create([
            'user_id'         => $this->user->id,
            'kategori_barang' => 'Pempek',
            'nama_barang'     => 'Pempek Kapal Selam',
            'jumlah_stok'     => 20,
            'tanggal_update'  => now()
        ]);
    }

    public function test_stok_berkurang_saat_uang_masuk_disimpan()
    {
        $response = $this->actingAs($this->user)->post(route('account.debit.store'), [
            'nominal'     => '50,000',
            'debit_date'  => now()->format('Y-m-d H:i:s'),
            'category_id' => $this->category->id,
            'description' => 'Penjualan 5 pcs Pempek Kapal Selam',
            'stock_id'    => $this->stock->id,
            'qty'         => 5
        ]);

        $response->assertRedirect(route('account.debit.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('debit', [
            'user_id'     => $this->user->id,
            'category_id' => $this->category->id,
            'stock_id'    => $this->stock->id,
            'qty'         => 5,
            'nominal'     => 50000
        ]);

        $this->assertEquals(15, $this->stock->fresh()->jumlah_stok);
    }

    public function test_transaksi_ditolak_jika_qty_melebihi_stok()
    {
        $response = $this->actingAs($this->user)->from(route('account.debit.create'))->post(route('account.debit.store'), [
            'nominal'     => '500,000',
            'debit_date'  => now()->format('Y-m-d H:i:s'),
            'category_id' => $this->category->id,
            'description' => 'Penjualan 50 pcs',
            'stock_id'    => $this->stock->id,
            'qty'         => 50
        ]);

        $response->assertRedirect(route('account.debit.create'));
        $response->assertSessionHas('error');

        // Pastikan stok tidak berubah
        $this->assertEquals(20, $this->stock->fresh()->jumlah_stok);

        // Pastikan tidak ada data debit tersimpan
        $this->assertDatabaseMissing('debit', [
            'description' => 'Penjualan 50 pcs'
        ]);
    }

    public function test_uang_masuk_tanpa_barang_tidak_mengubah_stok()
    {
        $response = $this->actingAs($this->user)->post(route('account.debit.store'), [
            'nominal'     => '100,000',
            'debit_date'  => now()->format('Y-m-d H:i:s'),
            'category_id' => $this->category->id,
            'description' => 'Modal Tambahan',
            'stock_id'    => '',
            'qty'         => ''
        ]);

        $response->assertRedirect(route('account.debit.index'));
        $this->assertDatabaseHas('debit', [
            'user_id'     => $this->user->id,
            'stock_id'    => null,
            'qty'         => null,
            'nominal'     => 100000
        ]);

        $this->assertEquals(20, $this->stock->fresh()->jumlah_stok);
    }

    public function test_stok_dikembalikan_saat_debit_dihapus()
    {
        $debit = Debit::create([
            'user_id'     => $this->user->id,
            'category_id' => $this->category->id,
            'stock_id'    => $this->stock->id,
            'qty'         => 4,
            'nominal'     => 40000,
            'description' => 'Penjualan 4 pcs',
            'debit_date'  => now()
        ]);
        $this->stock->decrement('jumlah_stok', 4);
        $this->assertEquals(16, $this->stock->fresh()->jumlah_stok);

        $response = $this->actingAs($this->user)->delete(route('account.debit.destroy', $debit->id));
        $response->assertJson(['status' => 'success']);

        $this->assertEquals(20, $this->stock->fresh()->jumlah_stok);
        $this->assertDatabaseMissing('debit', ['id' => $debit->id]);
    }

    public function test_stok_disesuaikan_saat_debit_diupdate()
    {
        $debit = Debit::create([
            'user_id'     => $this->user->id,
            'category_id' => $this->category->id,
            'stock_id'    => $this->stock->id,
            'qty'         => 5,
            'nominal'     => 50000,
            'description' => 'Penjualan 5 pcs',
            'debit_date'  => now()
        ]);
        $this->stock->decrement('jumlah_stok', 5);
        $this->assertEquals(15, $this->stock->fresh()->jumlah_stok);

        // Update qty menjadi 8 (stok harus berkurang lagi 3, dari 15 menjadi 12)
        $response = $this->actingAs($this->user)->put(route('account.debit.update', $debit->id), [
            'nominal'     => '80,000',
            'debit_date'  => now()->format('Y-m-d H:i:s'),
            'category_id' => $this->category->id,
            'description' => 'Revisi penjualan jadi 8 pcs',
            'stock_id'    => $this->stock->id,
            'qty'         => 8
        ]);

        $response->assertRedirect(route('account.debit.index'));
        $this->assertEquals(12, $this->stock->fresh()->jumlah_stok);
        $this->assertDatabaseHas('debit', [
            'id'      => $debit->id,
            'qty'     => 8,
            'nominal' => 80000
        ]);
    }
}
