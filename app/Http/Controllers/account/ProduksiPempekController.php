<?php

namespace App\Http\Controllers\account;

use App\Http\Controllers\Controller;
use App\MasterPempek;
use App\ProduksiDetail;
use App\ProduksiHeader;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProduksiPempekController extends Controller
{
    /**
     * ProduksiPempekController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of production headers.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $produksi = ProduksiHeader::with(['details.pempek'])
            ->where('user_id', Auth::user()->id)
            ->orderBy('tanggal', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return view('account.produksi.index', compact('produksi'));
    }

    /**
     * Search production by no_faktur or keterangan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $search = $request->get('q');
        $produksi = ProduksiHeader::with(['details.pempek'])
            ->where('user_id', Auth::user()->id)
            ->where(function ($query) use ($search) {
                $query->where('no_faktur', 'LIKE', '%' . $search . '%')
                    ->orWhere('keterangan', 'LIKE', '%' . $search . '%');
            })
            ->orderBy('tanggal', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return view('account.produksi.index', compact('produksi'));
    }

    /**
     * Show the form for creating a new production batch.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pempekList = MasterPempek::where('user_id', Auth::user()->id)
            ->orderBy('nama_pempek', 'ASC')
            ->get();

        $today = Carbon::now()->format('Ymd');
        $prefix = 'PRD-' . $today . '-';
        $count = ProduksiHeader::where('user_id', Auth::user()->id)
            ->where('no_faktur', 'LIKE', $prefix . '%')
            ->count() + 1;
        $autoNoFaktur = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
        while (ProduksiHeader::where('no_faktur', $autoNoFaktur)->exists()) {
            $count++;
            $autoNoFaktur = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
        }

        return view('account.produksi.create', compact('pempekList', 'autoNoFaktur'));
    }

    /**
     * Store a newly created production batch with atomic stock mutation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'tanggal'                 => 'required',
            'items'                   => 'required|array|min:1',
            'items.*.kode_pempek'     => 'required|string',
            'items.*.jumlah_produksi' => 'required|integer|min:1',
        ], [
            'tanggal.required'                 => 'Pilih tanggal transaksi produksi!',
            'items.required'                   => 'Minimal harus ada 1 item pempek yang diproduksi!',
            'items.min'                        => 'Minimal harus ada 1 item pempek yang diproduksi!',
            'items.*.kode_pempek.required'     => 'Pilih produk pempek!',
            'items.*.jumlah_produksi.required' => 'Masukkan jumlah produksi!',
            'items.*.jumlah_produksi.min'      => 'Jumlah produksi minimal 1!',
        ]);

        $today = Carbon::now()->format('Ymd');
        $prefix = 'PRD-' . $today . '-';

        DB::beginTransaction();
        try {
            $count = ProduksiHeader::where('user_id', Auth::user()->id)
                ->where('no_faktur', 'LIKE', $prefix . '%')
                ->count() + 1;
            $noFaktur = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
            while (ProduksiHeader::where('no_faktur', $noFaktur)->exists()) {
                $count++;
                $noFaktur = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
            }

            $header = ProduksiHeader::create([
                'no_faktur'  => $noFaktur,
                'user_id'    => Auth::user()->id,
                'tanggal'    => $request->input('tanggal'),
                'keterangan' => $request->input('keterangan'),
            ]);

            foreach ($request->input('items') as $item) {
                $pempek = MasterPempek::where('kode_pempek', $item['kode_pempek'])
                    ->where('user_id', Auth::user()->id)
                    ->lockForUpdate()
                    ->first();

                if (!$pempek) {
                    throw new \Exception("Produk pempek dengan kode {$item['kode_pempek']} tidak ditemukan!");
                }

                ProduksiDetail::create([
                    'no_faktur'       => $header->no_faktur,
                    'kode_pempek'     => $pempek->kode_pempek,
                    'jumlah_produksi' => (int) $item['jumlah_produksi'],
                ]);

                // Mutasi stok bertambah (+)
                $pempek->increment('stok', (int) $item['jumlah_produksi']);
            }

            DB::commit();

            return redirect()->route('account.produksi.index')
                ->with('success', "Faktur Produksi {$noFaktur} Berhasil Disimpan & Stok Bertambah!");
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan transaksi produksi: ' . $e->getMessage());
        }
    }

    /**
     * Display details of a specific production batch.
     *
     * @param  string  $no_faktur
     * @return \Illuminate\Http\Response
     */
    public function show($no_faktur)
    {
        $produksi = ProduksiHeader::with(['details.pempek'])
            ->where('no_faktur', $no_faktur)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();

        return view('account.produksi.detail', compact('produksi'));
    }
}
