@extends('layouts.account')

@section('title')
    Rincian Faktur Produksi {{ $produksi->no_faktur }} - {{ config('app.name') }}
@stop

@section('content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>DETAIL FAKTUR PRODUKSI</h1>
            </div>

            <div class="section-body">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4><i class="fas fa-file-invoice"></i> RINCIAN FAKTUR: {{ $produksi->no_faktur }}</h4>
                        <a href="{{ route('account.produksi.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>

                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded border">
                                    <small class="text-muted d-block font-weight-bold">NOMOR FAKTUR</small>
                                    <span class="font-weight-bold text-primary" style="font-size: 16px;">{{ $produksi->no_faktur }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded border">
                                    <small class="text-muted d-block font-weight-bold">TANGGAL & WAKTU PRODUKSI</small>
                                    <span class="font-weight-bold" style="font-size: 16px;">{{ date('d F Y, H:i', strtotime($produksi->tanggal)) }}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded border">
                                    <small class="text-muted d-block font-weight-bold">KETERANGAN</small>
                                    <span style="font-size: 15px;">{{ $produksi->keterangan ?: '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <h6 class="font-weight-bold mb-3"><i class="fas fa-boxes"></i> DAFTAR BARANG YANG DIPRODUKSI</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="thead-light">
                                <tr>
                                    <th style="width: 6%; text-align: center">NO.</th>
                                    <th style="width: 18%">KODE PEMPEK</th>
                                    <th>NAMA VARIAN PEMPEK</th>
                                    <th style="width: 18%">BAHAN BAKU IKAN</th>
                                    <th style="width: 20%; text-align: center">KUANTITAS PRODUKSI</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($produksi->details as $idx => $detail)
                                    <tr>
                                        <td class="text-center font-weight-bold">{{ $idx + 1 }}</td>
                                        <td>
                                            <span class="badge badge-light border font-weight-bold">{{ $detail->kode_pempek }}</span>
                                        </td>
                                        <td class="font-weight-600">{{ $detail->pempek ? $detail->pempek->nama_pempek : 'Produk Tidak Ditemukan' }}</td>
                                        <td>
                                            <span class="badge badge-info">{{ $detail->pempek ? $detail->pempek->jenis_ikan : '-' }}</span>
                                        </td>
                                        <td class="text-center font-weight-bold text-success" style="font-size: 15px;">
                                            +{{ number_format($detail->jumlah_produksi, 0, ',', '.') }} pcs
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <td colspan="4" class="text-right">TOTAL KESELURUHAN HASIL PRODUKSI:</td>
                                    <td class="text-center text-success" style="font-size: 16px;">
                                        +{{ number_format($produksi->details->sum('jumlah_produksi'), 0, ',', '.') }} pcs
                                    </td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('account.produksi.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Kembali ke Daftar Faktur
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@stop
