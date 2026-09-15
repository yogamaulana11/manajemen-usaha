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
