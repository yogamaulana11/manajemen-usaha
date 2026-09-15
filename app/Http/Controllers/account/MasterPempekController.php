<?php

namespace App\Http\Controllers\account;

use App\Http\Controllers\Controller;
use App\MasterPempek;
use App\StockBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MasterPempekController extends Controller
{
    /**
     * MasterPempekController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of master pempek.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pempek = MasterPempek::where('user_id', Auth::user()->id)
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return view('account.master_pempek.index', compact('pempek'));
    }

    /**
     * Search master pempek by keyword.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $search = $request->get('q');
        $pempek = MasterPempek::where('user_id', Auth::user()->id)
            ->where(function ($query) use ($search) {
                $query->where('kode_pempek', 'LIKE', '%' . $search . '%')
                    ->orWhere('nama_pempek', 'LIKE', '%' . $search . '%')
                    ->orWhere('jenis_ikan', 'LIKE', '%' . $search . '%');
            })
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return view('account.master_pempek.index', compact('pempek'));
    }

    /**
     * Show the form for creating a new master pempek.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Generate auto next code
        $count = MasterPempek::where('user_id', Auth::user()->id)->count() + 1;
        $autoCode = 'PMP-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        while (MasterPempek::where('kode_pempek', $autoCode)->exists()) {
            $count++;
            $autoCode = 'PMP-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        }

        $stockBarang = StockBarang::where('user_id', Auth::user()->id)->get();

        return view('account.master_pempek.create', compact('autoCode', 'stockBarang'));
    }

    /**
     * Store a newly created master pempek in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'kode_pempek' => 'required|string|max:20|unique:master_pempek,kode_pempek',
            'nama_pempek' => 'required|string|max:100',
            'jenis_ikan'  => 'required|string|max:50',
            'harga'       => 'required',
            'foto'        => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
        ], [
            'kode_pempek.required' => 'Masukkan kode pempek!',
            'kode_pempek.unique'   => 'Kode pempek ini sudah digunakan!',
            'nama_pempek.required' => 'Masukkan nama pempek!',
            'jenis_ikan.required'  => 'Masukkan jenis ikan!',
            'harga.required'       => 'Masukkan harga!',
            'foto.image'           => 'File harus berupa gambar (jpeg, jpg, png)!',
            'foto.max'             => 'Ukuran foto maksimal 2MB!',
        ]);

        $cleanHarga = str_replace([',', '.'], '', $request->input('harga'));

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $image = $request->file('foto');
            $imagePath = $image->store('public/pempek');
            $fotoPath = basename($imagePath);
        }

        $pempek = MasterPempek::create([
            'kode_pempek' => strtoupper($request->input('kode_pempek')),
            'user_id'     => Auth::user()->id,
            'nama_pempek' => $request->input('nama_pempek'),
            'jenis_ikan'  => $request->input('jenis_ikan'),
            'harga'       => $cleanHarga,
            'foto'        => $fotoPath,
            'stok'        => 0,
        ]);

        if ($pempek) {
            return redirect()->route('account.master_pempek.index')->with('success', 'Data Pempek Berhasil Ditambahkan!');
        } else {
            return redirect()->route('account.master_pempek.index')->with('error', 'Data Pempek Gagal Ditambahkan!');
        }
    }

    /**
     * Show the form for editing the specified master pempek.
     *
     * @param  string  $kode_pempek
     * @return \Illuminate\Http\Response
     */
    public function edit($kode_pempek)
    {
        $pempek = MasterPempek::where('kode_pempek', $kode_pempek)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();

        return view('account.master_pempek.edit', compact('pempek'));
    }

    /**
     * Update the specified master pempek in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $kode_pempek
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $kode_pempek)
    {
        $pempek = MasterPempek::where('kode_pempek', $kode_pempek)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();

        $this->validate($request, [
            'nama_pempek' => 'required|string|max:100',
            'jenis_ikan'  => 'required|string|max:50',
            'harga'       => 'required',
            'foto'        => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
        ], [
            'nama_pempek.required' => 'Masukkan nama pempek!',
            'jenis_ikan.required'  => 'Masukkan jenis ikan!',
            'harga.required'       => 'Masukkan harga!',
            'foto.image'           => 'File harus berupa gambar (jpeg, jpg, png)!',
            'foto.max'             => 'Ukuran foto maksimal 2MB!',
        ]);
        $cleanHarga = str_replace([',', '.'], '', $request->input('harga'));

        $fotoPath = $pempek->foto;
        if ($request->hasFile('foto')) {
            if ($pempek->foto && Storage::exists('public/pempek/' . $pempek->foto)) {
                Storage::delete('public/pempek/' . $pempek->foto);
            }
            $image = $request->file('foto');
            $imagePath = $image->store('public/pempek');
            $fotoPath = basename($imagePath);
        }

        $update = $pempek->update([
            'nama_pempek' => $request->input('nama_pempek'),
            'jenis_ikan'  => $request->input('jenis_ikan'),
            'harga'       => $cleanHarga,
            'foto'        => $fotoPath,
        ]);

        if ($update) {
            return redirect()->route('account.master_pempek.index')->with('success', 'Data Pempek Berhasil Diperbarui!');
        } else {
            return redirect()->route('account.master_pempek.index')->with('error', 'Data Pempek Gagal Diperbarui!');
        }
    }

    /**
     * Remove the specified master pempek from storage.
     *
     * @param  string  $kode_pempek
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($kode_pempek)
    {
        $pempek = MasterPempek::where('kode_pempek', $kode_pempek)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();

        if ($pempek->produksiDetails()->exists() || $pempek->penjualanDetails()->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Produk ini tidak dapat dihapus karena telah memiliki riwayat transaksi!'
            ], 422);
        }

        if ($pempek->foto && Storage::exists('public/pempek/' . $pempek->foto)) {
            Storage::delete('public/pempek/' . $pempek->foto);
        }

        $delete = $pempek->delete();

        if ($delete) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Data Pempek Berhasil Dihapus!'
            ]);
        } else {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data Pempek Gagal Dihapus!'
            ]);
        }
    }
}
