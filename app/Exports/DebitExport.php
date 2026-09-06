<?php

namespace App\Exports;

use App\Debit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DebitExport implements FromView, ShouldAutoSize
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
        $debit = Debit::select(
            'debit.id',
            'debit.category_id',
            'debit.user_id',
            'debit.stock_id',
            'debit.qty',
            'debit.nominal',
            'debit.debit_date',
            'debit.description',
            'categories_debit.name as category_name',
            'stock_barang.nama_barang'
        )
            ->leftJoin('categories_debit', 'debit.category_id', '=', 'categories_debit.id')
            ->leftJoin('stock_barang', 'debit.stock_id', '=', 'stock_barang.id')
            ->where('debit.user_id', Auth::user()->id)
            ->whereDate('debit.debit_date', '>=', $this->tanggal_awal)
            ->whereDate('debit.debit_date', '<=', $this->tanggal_akhir)
            ->orderBy('debit.debit_date', 'ASC')
            ->get();

        $total_nominal = $debit->sum('nominal');

        return view('account.laporan_debit.excel', [
            'debit'         => $debit,
            'total_nominal' => $total_nominal,
            'tanggal_awal'  => $this->tanggal_awal,
            'tanggal_akhir' => $this->tanggal_akhir,
        ]);
    }
}
