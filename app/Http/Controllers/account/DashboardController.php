<?php

namespace App\Http\Controllers\account;

use App\Debit;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * DashboardController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $userId = Auth::user()->id;
        $currentYear = Carbon::now()->year;
        $selected_year = (int) $request->get('year', $currentYear);

        $uang_masuk_bulan_ini = DB::table('debit')
            ->selectRaw('sum(nominal) as nominal')
            ->whereYear('debit_date', Carbon::now()->year)
            ->whereMonth('debit_date', Carbon::now()->month)
            ->where('user_id', $userId)
            ->first();

        $uang_keluar_bulan_ini = DB::table('credit')
            ->selectRaw('sum(nominal) as nominal')
            ->whereYear('credit_date', Carbon::now()->year)
            ->whereMonth('credit_date', Carbon::now()->month)
            ->where('user_id', $userId)
            ->first();

        $uang_masuk_bulan_lalu = DB::table('debit')
            ->selectRaw('sum(nominal) as nominal')
            ->whereYear('debit_date', Carbon::now()->year)
            ->whereMonth('debit_date', Carbon::now()->subMonths(1)->month)
            ->where('user_id', $userId)
            ->first();

        $uang_keluar_bulan_lalu = DB::table('credit')
            ->selectRaw('sum(nominal) as nominal')
            ->whereYear('credit_date', Carbon::now()->year)
            ->whereMonth('credit_date', Carbon::now()->subMonths(1)->month)
            ->where('user_id', $userId)
            ->first();

        $uang_masuk_selama_ini = DB::table('debit')
            ->selectRaw('sum(nominal) as nominal')
            ->where('user_id', $userId)
            ->first();

        $uang_keluar_selama_ini = DB::table('credit')
            ->selectRaw('sum(nominal) as nominal')
            ->where('user_id', $userId)
            ->first();

        // Saldo ringkasan
        $saldo_bulan_ini = ($uang_masuk_bulan_ini->nominal ?? 0) - ($uang_keluar_bulan_ini->nominal ?? 0);
        $saldo_bulan_lalu = ($uang_masuk_bulan_lalu->nominal ?? 0) - ($uang_keluar_bulan_lalu->nominal ?? 0);
        $saldo_selama_ini = ($uang_masuk_selama_ini->nominal ?? 0) - ($uang_keluar_selama_ini->nominal ?? 0);

        // Ambil daftar tahun dari transaksi debit & credit
        $debit_years = DB::table('debit')
            ->where('user_id', $userId)
            ->selectRaw('DISTINCT YEAR(debit_date) as year')
            ->pluck('year')
            ->toArray();

        $credit_years = DB::table('credit')
            ->where('user_id', $userId)
            ->selectRaw('DISTINCT YEAR(credit_date) as year')
            ->pluck('year')
            ->toArray();

        $year_list = array_values(array_unique(array_merge([$currentYear], $debit_years, $credit_years)));
        rsort($year_list);

        // Agregasi bulanan debit untuk tahun terpilih
        $debit_monthly = DB::table('debit')
            ->selectRaw('MONTH(debit_date) as month, sum(nominal) as total')
            ->where('user_id', $userId)
            ->whereYear('debit_date', $selected_year)
            ->groupBy(DB::raw('MONTH(debit_date)'))
            ->pluck('total', 'month')
            ->toArray();

        // Agregasi bulanan credit untuk tahun terpilih
        $credit_monthly = DB::table('credit')
            ->selectRaw('MONTH(credit_date) as month, sum(nominal) as total')
            ->where('user_id', $userId)
            ->whereYear('credit_date', $selected_year)
            ->groupBy(DB::raw('MONTH(credit_date)'))
            ->pluck('total', 'month')
            ->toArray();

        $chart_debit = [];
        $chart_credit = [];
        for ($m = 1; $m <= 12; $m++) {
            $chart_debit[] = (int) ($debit_monthly[$m] ?? 0);
            $chart_credit[] = (int) ($credit_monthly[$m] ?? 0);
        }

        return view('account.dashboard.index', compact(
            'saldo_selama_ini',
            'saldo_bulan_ini',
            'saldo_bulan_lalu',
            'selected_year',
            'year_list',
            'chart_debit',
            'chart_credit'
        ));
    }
}
