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
