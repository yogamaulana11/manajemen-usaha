<?php

namespace App\Http\Controllers\account;

use App\Credit;
use App\Exports\CreditExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class LaporanCreditController extends Controller
{
    /**
     * LaporanCreditController constructor.
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
        return view('account.laporan_credit.index');
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

        $credit = Credit::select(
            'credit.id',
            'credit.category_id',
            'credit.user_id',
            'credit.nominal',
            'credit.credit_date',
            'credit.description',
            'categories_credit.id as id_category',
            'categories_credit.name'
        )
            ->leftJoin('categories_credit', 'credit.category_id', '=', 'categories_credit.id')
            ->where('credit.user_id', Auth::user()->id)
            ->whereDate('credit.credit_date', '>=', $tanggal_awal)
            ->whereDate('credit.credit_date', '<=', $tanggal_akhir)
            ->orderBy('credit.credit_date', 'ASC')
            ->paginate(10)
            ->appends(request()->except('page'));

        return view('account.laporan_credit.index', compact('credit', 'tanggal_awal', 'tanggal_akhir'));
    }

    /**
     * Export laporan uang keluar ke Excel (.xlsx)
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

        $filename = 'laporan-uang-keluar-' . $tanggal_awal . '-sd-' . $tanggal_akhir . '.xlsx';

        return Excel::download(new CreditExport($tanggal_awal, $tanggal_akhir), $filename);
    }
}
