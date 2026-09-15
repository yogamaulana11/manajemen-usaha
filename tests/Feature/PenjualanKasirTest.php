<?php

namespace Tests\Feature;

use App\CategoriesDebit;
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
        $response = $this->actingAs($this->user)->postJson(route('account.penjualan.store'), [
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

        $response->assertStatus(200);
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
        $response = $this->actingAs($this->user)->postJson(route('account.penjualan.store'), [
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

    public function test_can_view_sales_list_and_receipt()
    {
        $sale = PenjualanHeader::create([
            'no_faktur'    => 'INV-TEST-RECEIPT-01',
            'user_id'      => $this->user->id,
            'tanggal_jual' => now(),
            'total_bayar'  => 50000,
            'bayar'        => 50000,
            'kembalian'    => 0,
            'catatan'      => 'Bungkus'
        ]);

        $response = $this->actingAs($this->user)->get(route('account.penjualan.index'));
        $response->assertStatus(200);
        $response->assertSee('INV-TEST-RECEIPT-01');

        $receiptResponse = $this->actingAs($this->user)->get(route('account.penjualan.struk', $sale->no_faktur));
        $receiptResponse->assertStatus(200);
        $receiptResponse->assertSee('INV-TEST-RECEIPT-01');
    }
}
