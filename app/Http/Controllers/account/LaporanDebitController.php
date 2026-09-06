<?php

namespace App\Http\Controllers\account;

use App\Debit;
use App\Exports\DebitExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class LaporanDebitController extends Controller
{
    /**
     * LaporanDebitController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('account.laporan_debit.index');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Validation\ValidationException
     */
    public function check(Request $request)
    {
        //set validasi required
        $this->validate($request, [
            'tanggal_awal'     => 'required',
            'tanggal_akhir'    => 'required',
        ],
            //set message validation
            [
                'tanggal_awal.required'  => 'Silahkan Pilih Tanggal Awal!',
                'tanggal_akhir.required' => 'Silahkan Pilih Tanggal Akhir!',
            ]
        );

        $tanggal_awal  = $request->input('tanggal_awal');
        $tanggal_akhir = $request->input('tanggal_akhir');

        $debit = Debit::select(
            'debit.id',
            'debit.category_id',
            'debit.user_id',
            'debit.stock_id',
            'debit.qty',
            'debit.nominal',
            'debit.debit_date',
            'debit.description',
            'categories_debit.id as id_category',
            'categories_debit.name',
            'stock_barang.nama_barang'
        )
            ->leftJoin('categories_debit', 'debit.category_id', '=', 'categories_debit.id')
            ->leftJoin('stock_barang', 'debit.stock_id', '=', 'stock_barang.id')
            ->where('debit.user_id', Auth::user()->id)
            ->whereDate('debit.debit_date', '>=', $tanggal_awal)
            ->whereDate('debit.debit_date', '<=', $tanggal_akhir)
            ->orderBy('debit.debit_date', 'ASC')
            ->paginate(10)
            ->appends(request()->except('page'));

        return view('account.laporan_debit.index', compact('debit', 'tanggal_awal', 'tanggal_akhir'));
    }

    /**
     * Export laporan uang masuk ke Excel (.xlsx)
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        $this->validate($request, [
            'tanggal_awal'  => 'required',
            'tanggal_akhir' => 'required',
        ]);

        $tanggal_awal  = $request->input('tanggal_awal');
        $tanggal_akhir = $request->input('tanggal_akhir');

        $filename = 'laporan-uang-masuk-' . $tanggal_awal . '-sd-' . $tanggal_akhir . '.xlsx';

        return Excel::download(new DebitExport($tanggal_awal, $tanggal_akhir), $filename);
    }
}
