<?php

namespace App\Http\Controllers\account;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\CategoriesDebit;
use App\StockBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockBarangController extends Controller
{
    //

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the table of stock barang.
     *
     * @return void
     */
    public function index()
    {
        $stok = DB::table('stock_barang')
            ->select('stock_barang.id', 'stock_barang.kategori_barang', 'stock_barang.nama_barang', 'stock_barang.jumlah_stok', 'stock_barang.tanggal_update')
            ->orderBy('stock_barang.created_at', 'DESC')
            ->paginate(10);

        // dd($stok);
        return view('account.stock.index', compact('stok'));
    }

    /**
     * Create the stock barang.
     *
     * @param Request $request
     * @return void
     */

    public function create()
    {
        $categories = CategoriesDebit::where('user_id', Auth::user()->id)
            ->get();
        return view('account.stock.create', compact('categories'));
    }


    public function store(Request $request)
    {
        //set validasi required
        $this->validate(
            $request,
            [
                'kategori_barang'       => 'required',
                'nama_barang'    => 'required',
                'jumlah_stok'   => 'required',
                'tanggal_update'   => 'required'
            ],
            //set message validation
            [
                'kategori_barang.required' => 'Masukkan kategori barang',
                'nama_barang.required' => 'Masukan nama barang!',
                'jumlah_stok.required' => 'Masukkan jumlah stok!',
                'tanggal_update.required' => 'Masukkan tanggal update!',
            ]
        );

        //Eloquent simpan data
        $save = StockBarang::create([
            'user_id'       => Auth::user()->id,
            'kategori_barang'   => $request->input('kategori_barang'),
            'nama_barang'   => $request->input('nama_barang'),
            'jumlah_stok'   => $request->input('jumlah_stok'),
            'tanggal_update'   => $request->input('tanggal_update'),
        ]);
        //cek apakah data berhasil disimpan
        if ($save) {
            //redirect dengan pesan sukses
            return redirect()->route('account.stock.index')->with(['success' => 'Data Berhasil Disimpan!']);
        } else {
            //redirect dengan pesan error
            return redirect()->route('account.stock.index')->with(['error' => 'Data Gagal Disimpan!']);
        }
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $stok = StockBarang::find($id);
        //
        //  dd($stok);
        return  view('account.stock.edit', compact('stok'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $stok = StockBarang::find($id);
        //set validasi required
        $this->validate(
            $request,
            [
                'kategori_barang' => 'required',
                'nama_barang'    => 'required',
                'jumlah_stok'   => 'required',
                'tanggal_update'   => 'required'
            ],
            //set message validation
            [
                'kategori_barang.required' => 'Masukkan kategori barang',
                'nama_barang.required' => 'Masukan nama barang!',
                'jumlah_stok.required' => 'Masukkan jumlah stok!',
                'tanggal_update.required' => 'Masukkan tanggal update!',
            ]
        );

        //Eloquent simpan data
        $update = StockBarang::whereId($stok->id)->update([
            'user_id'       => Auth::user()->id,
            'kategori_barang'   => $request->input('kategori_barang'),
            'nama_barang'    => $request->input('nama_barang'),
            'jumlah_stok'   => $request->input('jumlah_stok'),
            'tanggal_update'   => $request->input('tanggal_update'),
        ]);
        //cek apakah data berhasil disimpan
        if ($update) {
            //redirect dengan pesan sukses
            return redirect()->route('account.stock.index')->with(['success' => 'Data Berhasil Diupdate!']);
        } else {
            //redirect dengan pesan error
            return redirect()->route('account.stock.index')->with(['error' => 'Data Gagal Diupdate!']);
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
        $delete = StockBarang::find($id)->delete($id);

        if ($delete) {
            return response()->json([
                'status' => 'success'
            ]);
        } else {
            return response()->json([
                'status' => 'error'
            ]);
        }
    }
    public function search(Request $request)
    {
        $search = $request->get('q');
        $stok = StockBarang::where('user_id', Auth::user()->id)
        ->where(function ($query) use ($search) {
            $query->where('nama_barang', 'LIKE', '%' . $search . '%')
                  ->orWhere('kategori_barang', 'LIKE', '%' . $search . '%');
        })
            ->orderBy('created_at', 'DESC')
            ->paginate(10);
        return view('account.stock.index', compact('stok'));
    }
}
