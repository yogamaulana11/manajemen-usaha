<?php

namespace App\Http\Controllers\account;

use App\CategoriesDebit;
use App\Debit;
use App\Http\Controllers\Controller;
use App\StockBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DebitController extends Controller
{
    /**
     * DebitController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $debit = DB::table('debit')
            ->select(
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
            ->orderBy('debit.created_at', 'DESC')
            ->paginate(10);

        return view('account.debit.index', compact('debit'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function search(Request $request)
    {
        $search = $request->get('q');
        $debit = DB::table('debit')
            ->select(
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
            ->where(function ($query) use ($search) {
                $query->where('debit.description', 'LIKE', '%' . $search . '%')
                    ->orWhere('stock_barang.nama_barang', 'LIKE', '%' . $search . '%');
            })
            ->orderBy('debit.created_at', 'DESC')
            ->paginate(10);

        return view('account.debit.index', compact('debit'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = CategoriesDebit::where('user_id', Auth::user()->id)
            ->orderBy('name', 'ASC')
            ->get();

        $stocks = StockBarang::where('user_id', Auth::user()->id)
            ->where('jumlah_stok', '>', 0)
            ->orderBy('nama_barang', 'ASC')
            ->get();

        return view('account.debit.create', compact('categories', 'stocks'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate(
            $request,
            [
                'nominal'       => 'required',
                'debit_date'    => 'required',
                'category_id'   => 'required',
                'description'   => 'required',
                'stock_id'      => 'nullable|exists:stock_barang,id',
                'qty'           => 'required_with:stock_id|nullable|integer|min:1',
            ],
            [
                'nominal.required'     => 'Masukkan Nominal Debit / Uang Masuk!',
                'debit_date.required'  => 'Silahkan Pilih Tanggal!',
                'category_id.required' => 'Silahkan Pilih Kategori!',
                'description.required' => 'Masukkan Keterangan!',
                'qty.required_with'    => 'Masukkan Jumlah Barang (Qty)!',
                'qty.min'              => 'Jumlah Barang minimal 1!',
            ]
        );

        try {
            DB::transaction(function () use ($request) {
                $stockId = $request->input('stock_id');
                $qty = $request->input('qty');

                if ($stockId) {
                    $stock = StockBarang::where('id', $stockId)
                        ->where('user_id', Auth::user()->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$stock || $stock->jumlah_stok < $qty) {
                        $sisa = $stock ? $stock->jumlah_stok : 0;
                        throw new \Exception("Stok barang tidak mencukupi! Sisa stok saat ini: {$sisa}");
                    }

                    $stock->decrement('jumlah_stok', $qty);
                    $stock->update(['tanggal_update' => now()]);
                }

                Debit::create([
                    'user_id'     => Auth::user()->id,
                    'debit_date'  => $request->input('debit_date'),
                    'category_id' => $request->input('category_id'),
                    'stock_id'    => $stockId ?: null,
                    'qty'         => $stockId ? $qty : null,
                    'nominal'     => str_replace(",", "", $request->input('nominal')),
                    'description' => $request->input('description'),
                ]);
            });

            return redirect()->route('account.debit.index')->with(['success' => 'Data Berhasil Disimpan!']);
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Debit  $debit
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, Debit $debit)
    {
        $categories = CategoriesDebit::where('user_id', Auth::user()->id)
            ->orderBy('name', 'ASC')
            ->get();

        $stocks = StockBarang::where('user_id', Auth::user()->id)
            ->orderBy('nama_barang', 'ASC')
            ->get();

        return view('account.debit.edit', compact('debit', 'categories', 'stocks'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Debit  $debit
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Debit $debit)
    {
        $this->validate(
            $request,
            [
                'nominal'       => 'required',
                'debit_date'    => 'required',
                'category_id'   => 'required',
                'description'   => 'required',
                'stock_id'      => 'nullable|exists:stock_barang,id',
                'qty'           => 'required_with:stock_id|nullable|integer|min:1',
            ],
            [
                'nominal.required'     => 'Masukkan Nominal Debit / Uang Masuk!',
                'debit_date.required'  => 'Silahkan Pilih Tanggal!',
                'category_id.required' => 'Silahkan Pilih Kategori!',
                'description.required' => 'Masukkan Keterangan!',
                'qty.required_with'    => 'Masukkan Jumlah Barang (Qty)!',
                'qty.min'              => 'Jumlah Barang minimal 1!',
            ]
        );

        try {
            DB::transaction(function () use ($request, $debit) {
                // 1. Kembalikan stok lama jika sebelumnya memilih barang
                if ($debit->stock_id && $debit->qty) {
                    $oldStock = StockBarang::where('id', $debit->stock_id)
                        ->where('user_id', Auth::user()->id)
                        ->lockForUpdate()
                        ->first();
                    if ($oldStock) {
                        $oldStock->increment('jumlah_stok', $debit->qty);
                        $oldStock->update(['tanggal_update' => now()]);
                    }
                }

                // 2. Potong stok baru jika barang dipilih
                $newStockId = $request->input('stock_id');
                $newQty = $request->input('qty');

                if ($newStockId) {
                    $newStock = StockBarang::where('id', $newStockId)
                        ->where('user_id', Auth::user()->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$newStock || $newStock->jumlah_stok < $newQty) {
                        $sisa = $newStock ? $newStock->jumlah_stok : 0;
                        throw new \Exception("Stok barang tidak mencukupi! Sisa stok saat ini: {$sisa}");
                    }

                    $newStock->decrement('jumlah_stok', $newQty);
                    $newStock->update(['tanggal_update' => now()]);
                }

                // 3. Update data debit
                $debit->update([
                    'category_id' => $request->input('category_id'),
                    'stock_id'    => $newStockId ?: null,
                    'qty'         => $newStockId ? $newQty : null,
                    'debit_date'  => $request->input('debit_date'),
                    'nominal'     => str_replace(",", "", $request->input('nominal')),
                    'description' => $request->input('description'),
                ]);
            });

            return redirect()->route('account.debit.index')->with(['success' => 'Data Berhasil Diupdate!']);
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $debit = Debit::where('id', $id)
                    ->where('user_id', Auth::user()->id)
                    ->firstOrFail();

                if ($debit->stock_id && $debit->qty) {
                    $stock = StockBarang::where('id', $debit->stock_id)
                        ->where('user_id', Auth::user()->id)
                        ->lockForUpdate()
                        ->first();
                    if ($stock) {
                        $stock->increment('jumlah_stok', $debit->qty);
                        $stock->update(['tanggal_update' => now()]);
                    }
                }

                $debit->delete();
            });

            return response()->json([
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
