<?php

namespace App\Exports;

use App\Credit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CreditExport implements FromView, ShouldAutoSize
{
    protected $tanggal_awal;
    protected $tanggal_akhir;

    public function __construct($tanggal_awal, $tanggal_akhir)
    {
        $this->tanggal_awal = $tanggal_awal;
        $this->tanggal_akhir = $tanggal_akhir;
    }

    public function view(): View
    {
        $credit = Credit::select(
            'credit.id',
            'credit.category_id',
            'credit.user_id',
            'credit.nominal',
            'credit.credit_date',
            'credit.description',
            'categories_credit.name as category_name'
        )
            ->leftJoin('categories_credit', 'credit.category_id', '=', 'categories_credit.id')
            ->where('credit.user_id', Auth::user()->id)
            ->whereDate('credit.credit_date', '>=', $this->tanggal_awal)
            ->whereDate('credit.credit_date', '<=', $this->tanggal_akhir)
            ->orderBy('credit.credit_date', 'ASC')
            ->get();

        $total_nominal = $credit->sum('nominal');

        return view('account.laporan_credit.excel', [
            'credit'        => $credit,
            'total_nominal' => $total_nominal,
            'tanggal_awal'  => $this->tanggal_awal,
            'tanggal_akhir' => $this->tanggal_akhir,
        ]);
    }
}
