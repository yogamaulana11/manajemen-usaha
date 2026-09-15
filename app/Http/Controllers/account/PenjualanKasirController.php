<?php

namespace App\Http\Controllers\account;

use App\CategoriesDebit;
use App\Debit;
use App\Http\Controllers\Controller;
use App\MasterPempek;
use App\PenjualanDetail;
use App\PenjualanHeader;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PenjualanKasirController extends Controller
{
    /**
     * PenjualanKasirController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of sales receipts.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $penjualan = PenjualanHeader::with(['details.pempek'])
            ->where('user_id', Auth::user()->id)
            ->orderBy('tanggal_jual', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return view('account.penjualan.index', compact('penjualan'));
    }

    /**
     * Search sales by no_faktur or catatan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $search = $request->get('q');
        $penjualan = PenjualanHeader::with(['details.pempek'])
            ->where('user_id', Auth::user()->id)
            ->where(function ($query) use ($search) {
                $query->where('no_faktur', 'LIKE', '%' . $search . '%')
                    ->orWhere('catatan', 'LIKE', '%' . $search . '%');
            })
            ->orderBy('tanggal_jual', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return view('account.penjualan.index', compact('penjualan'));
    }

    /**
     * Show the POS cashier checkout page.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pempekList = MasterPempek::where('user_id', Auth::user()->id)
            ->orderBy('nama_pempek', 'ASC')
            ->get();

        $today = Carbon::now()->format('Ymd');
        $prefix = 'INV-' . $today . '-';
        $count = PenjualanHeader::where('user_id', Auth::user()->id)
            ->where('no_faktur', 'LIKE', $prefix . '%')
            ->count() + 1;
        $autoNoFaktur = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
        while (PenjualanHeader::where('no_faktur', $autoNoFaktur)->exists()) {
            $count++;
            $autoNoFaktur = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
        }

        return view('account.penjualan.create', compact('pempekList', 'autoNoFaktur'));
    }

    /**
     * Store a newly created sale transaction with atomic stock mutation & debit recording.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'tanggal_jual'        => 'required',
            'bayar'               => 'required',
            'items'               => 'required|array|min:1',
            'items.*.kode_pempek' => 'required|string',
            'items.*.jumlah_jual' => 'required|integer|min:1',
        ], [
            'tanggal_jual.required'        => 'Pilih waktu transaksi penjualan!',
            'bayar.required'               => 'Masukkan nominal uang pembayaran!',
            'items.required'               => 'Keranjang belanja kasir masih kosong!',
            'items.min'                    => 'Minimal harus ada 1 item pempek yang dibeli!',
            'items.*.kode_pempek.required' => 'Kode pempek tidak valid!',
            'items.*.jumlah_jual.required' => 'Jumlah beli wajib diisi!',
            'items.*.jumlah_jual.min'      => 'Jumlah beli minimal 1 pcs!',
        ]);

        $cleanBayar = (float) str_replace([',', '.'], '', $request->input('bayar'));

        DB::beginTransaction();
        try {
            $today = Carbon::now()->format('Ymd');
            $prefix = 'INV-' . $today . '-';
            $count = PenjualanHeader::where('user_id', Auth::user()->id)
                ->where('no_faktur', 'LIKE', $prefix . '%')
                ->count() + 1;
            $noFaktur = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
            while (PenjualanHeader::where('no_faktur', $noFaktur)->exists()) {
                $count++;
                $noFaktur = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
            }

            $calculatedTotal = 0;
            $itemsToProcess = [];

            // Validasi stok & kalkulasi harga langsung dari server-side
            foreach ($request->input('items') as $item) {
                $pempek = MasterPempek::where('kode_pempek', $item['kode_pempek'])
                    ->where('user_id', Auth::user()->id)
                    ->lockForUpdate()
                    ->first();

                if (!$pempek) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => 'error',
                        'message' => "Produk pempek [{$item['kode_pempek']}] tidak ditemukan!"
                    ], 422);
                }

                $qty = (int) $item['jumlah_jual'];

                // Validasi Stok Tersedia
                if ($qty > $pempek->stok) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => 'error',
                        'message' => "Stok untuk {$pempek->nama_pempek} ({$pempek->kode_pempek}) tidak mencukupi! Sisa stok saat ini: {$pempek->stok} pcs, kuantitas beli: {$qty} pcs."
                    ], 422);
                }

                $harga = (float) $pempek->harga;
                $subtotal = $harga * $qty;
                $calculatedTotal += $subtotal;

                $itemsToProcess[] = [
                    'pempek'      => $pempek,
                    'kode_pempek' => $pempek->kode_pempek,
                    'harga'       => $harga,
                    'jumlah_jual' => $qty,
                    'subtotal'    => $subtotal,
                ];
            }

            // Validasi nominal bayar
            if ($cleanBayar < $calculatedTotal) {
                DB::rollBack();
                $kekurangan = $calculatedTotal - $cleanBayar;
                return response()->json([
                    'status'  => 'error',
                    'message' => "Uang pembayaran kurang Rp. " . number_format($kekurangan, 0, ',', '.') . "! Total tagihan: Rp. " . number_format($calculatedTotal, 0, ',', '.') . ", uang diterima: Rp. " . number_format($cleanBayar, 0, ',', '.') . "."
                ], 422);
            }

            $kembalian = $cleanBayar - $calculatedTotal;

            // Simpan Header Penjualan
            $header = PenjualanHeader::create([
                'no_faktur'    => $noFaktur,
                'user_id'      => Auth::user()->id,
                'tanggal_jual' => $request->input('tanggal_jual'),
                'total_bayar'  => $calculatedTotal,
                'bayar'        => $cleanBayar,
                'kembalian'    => $kembalian,
                'catatan'      => $request->input('catatan'),
            ]);

            // Simpan Detail & Potong Stok
            foreach ($itemsToProcess as $processed) {
                PenjualanDetail::create([
                    'no_faktur'   => $header->no_faktur,
                    'kode_pempek' => $processed['kode_pempek'],
                    'harga'       => $processed['harga'],
                    'jumlah_jual' => $processed['jumlah_jual'],
                    'subtotal'    => $processed['subtotal'],
                ]);

                // Mutasi stok berkurang (-)
                $processed['pempek']->decrement('stok', $processed['jumlah_jual']);
            }

            // Integrasi ke Uang Masuk (Debit)
            $category = CategoriesDebit::firstOrCreate(
                ['user_id' => Auth::user()->id, 'name' => 'Penjualan Pempek']
            );

            Debit::create([
                'user_id'     => Auth::user()->id,
                'category_id' => $category->id,
                'nominal'     => $calculatedTotal,
                'debit_date'  => $request->input('tanggal_jual'),
                'description' => 'Penjualan Kasir Faktur: ' . $header->no_faktur,
            ]);

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'      => 'success',
                    'message'     => "Transaksi {$noFaktur} Berhasil Diproses!",
                    'no_faktur'   => $header->no_faktur,
                    'total_bayar' => $calculatedTotal,
                    'bayar'       => $cleanBayar,
                    'kembalian'   => $kembalian,
                    'struk_url'   => route('account.penjualan.struk', $header->no_faktur),
                ]);
            }

            return redirect()->route('account.penjualan.index')
                ->with('success', "Transaksi {$noFaktur} Berhasil Disimpan!");
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal memproses transaksi kasir: ' . $e->getMessage());
        }
    }

    /**
     * Display sale details.
     *
     * @param  string  $no_faktur
     * @return \Illuminate\Http\Response
     */
    public function show($no_faktur)
    {
        $penjualan = PenjualanHeader::with(['details.pempek'])
            ->where('no_faktur', $no_faktur)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();

        return view('account.penjualan.detail', compact('penjualan'));
    }

    /**
     * Display printable thermal receipt.
     *
     * @param  string  $no_faktur
     * @return \Illuminate\Http\Response
     */
    public function struk($no_faktur)
    {
        $penjualan = PenjualanHeader::with(['details.pempek', 'user'])
            ->where('no_faktur', $no_faktur)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();

        return view('account.penjualan.struk', compact('penjualan'));
    }
}
