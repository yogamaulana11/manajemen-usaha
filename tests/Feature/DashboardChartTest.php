<?php

namespace Tests\Feature;

use App\CategoriesCredit;
use App\CategoriesDebit;
use App\Credit;
use App\Debit;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DashboardChartTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
    }

    public function test_dashboard_menampilkan_grafik_dan_data_bulanan()
    {
        $catDebit = CategoriesDebit::create(['user_id' => $this->user->id, 'name' => 'Penjualan']);
        $catCredit = CategoriesCredit::create(['user_id' => $this->user->id, 'name' => 'Operasional']);

        // Data tahun 2025 bulan 3 (Maret) dan bulan 7 (Juli)
        Debit::create([
            'user_id' => $this->user->id,
            'category_id' => $catDebit->id,
            'nominal' => 1500000,
            'description' => 'Pemasukan Maret 2025',
            'debit_date' => '2025-03-10 10:00:00'
        ]);

        Credit::create([
            'user_id' => $this->user->id,
            'category_id' => $catCredit->id,
            'nominal' => 500000,
            'description' => 'Pengeluaran Maret 2025',
            'credit_date' => '2025-03-15 11:00:00'
        ]);

        // Filter tahun 2025
        $response = $this->actingAs($this->user)->get(route('account.dashboard.index', ['year' => 2025]));

        $response->assertStatus(200);
        $response->assertViewHas('selected_year', 2025);
        $response->assertViewHas('year_list');
        $response->assertViewHas('chart_debit');
        $response->assertViewHas('chart_credit');

        $chartDebit = $response->viewData('chart_debit');
        $chartCredit = $response->viewData('chart_credit');

        // Bulan ke-3 (index 2): Maret
        $this->assertEquals(1500000, $chartDebit[2]);
        $this->assertEquals(500000, $chartCredit[2]);

        // Bulan lain bernilai 0
        $this->assertEquals(0, $chartDebit[0]); // Januari
    }
}
