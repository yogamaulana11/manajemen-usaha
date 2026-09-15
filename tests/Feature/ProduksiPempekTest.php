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

        $this->assertDatabaseHas('produksi_detail', [
            'kode_pempek'     => $this->p2->kode_pempek,
            'jumlah_produksi' => 15
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

    public function test_user_can_view_production_list_and_details()
    {
        $prod = ProduksiHeader::create([
            'no_faktur'  => 'PRD-TEST-VIEW-01',
            'user_id'    => $this->user->id,
            'tanggal'    => now(),
            'keterangan' => 'Batch Khusus'
        ]);

        $response = $this->actingAs($this->user)->get(route('account.produksi.index'));
        $response->assertStatus(200);
        $response->assertSee('PRD-TEST-VIEW-01');

        $showResponse = $this->actingAs($this->user)->get(route('account.produksi.show', $prod->no_faktur));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('PRD-TEST-VIEW-01');
        $showResponse->assertSee('Batch Khusus');
    }
}
