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
