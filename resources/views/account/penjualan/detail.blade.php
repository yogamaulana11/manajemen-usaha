@extends('layouts.account')

@section('title')
    Rincian Nota Penjualan {{ $penjualan->no_faktur }} - {{ config('app.name') }}
@stop

@section('content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>DETAIL NOTA PENJUALAN</h1>
            </div>

            <div class="section-body">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4><i class="fas fa-receipt"></i> NOTA PENJUALAN: {{ $penjualan->no_faktur }}</h4>
                        <div>
                            <a href="{{ route('account.penjualan.struk', $penjualan->no_faktur) }}" target="_blank" class="btn btn-dark btn-sm mr-1">
                                <i class="fas fa-print"></i> Cetak Struk
                            </a>
                            <a href="{{ route('account.penjualan.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="p-3 bg-light rounded border">
                                    <small class="text-muted d-block font-weight-bold">NOMOR NOTA</small>
                                    <span class="font-weight-bold text-success" style="font-size: 16px;">{{ $penjualan->no_faktur }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 bg-light rounded border">
                                    <small class="text-muted d-block font-weight-bold">WAKTU TRANSAKSI</small>
                                    <span class="font-weight-bold" style="font-size: 15px;">{{ date('d F Y, H:i', strtotime($penjualan->tanggal_jual)) }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 bg-light rounded border">
                                    <small class="text-muted d-block font-weight-bold">TOTAL BELANJA</small>
                                    <span class="font-weight-bold text-primary" style="font-size: 16px;">Rp. {{ number_format($penjualan->total_bayar, 0, ',', '.') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 bg-light rounded border">
                                    <small class="text-muted d-block font-weight-bold">PEMBAYARAN / KEMBALI</small>
                                    <span class="font-weight-bold" style="font-size: 14px;">Bayar: Rp. {{ number_format($penjualan->bayar, 0, ',', '.') }}</span><br>
                                    <small class="text-success font-weight-bold">Kembali: Rp. {{ number_format($penjualan->kembalian, 0, ',', '.') }}</small>
                                </div>
                            </div>
                        </div>

                        @if($penjualan->catatan)
                            <div class="alert alert-light border mb-4">
                                <i class="fas fa-comment-alt text-info"></i> <strong>Catatan Pesanan:</strong> {{ $penjualan->catatan }}
                            </div>
                        @endif

                        <h6 class="font-weight-bold mb-3"><i class="fas fa-list-ul"></i> RINCIAN ITEM PEMBELIAN</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="thead-light">
                                <tr>
                                    <th style="width: 6%; text-align: center">NO.</th>
                                    <th style="width: 18%">KODE PEMPEK</th>
                                    <th>NAMA VARIAN PEMPEK</th>
                                    <th style="width: 15%; text-align: right">HARGA SATUAN</th>
                                    <th style="width: 15%; text-align: center">KUANTITAS</th>
                                    <th style="width: 20%; text-align: right">SUBTOTAL</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($penjualan->details as $idx => $detail)
                                    <tr>
                                        <td class="text-center font-weight-bold">{{ $idx + 1 }}</td>
                                        <td>
                                            <span class="badge badge-light border font-weight-bold">{{ $detail->kode_pempek }}</span>
                                        </td>
                                        <td class="font-weight-600">{{ $detail->pempek ? $detail->pempek->nama_pempek : 'Produk' }}</td>
                                        <td class="text-right">Rp. {{ number_format($detail->harga, 0, ',', '.') }}</td>
                                        <td class="text-center font-weight-bold text-danger">
                                            -{{ number_format($detail->jumlah_jual, 0, ',', '.') }} pcs
                                        </td>
                                        <td class="text-right font-weight-bold text-primary">
                                            Rp. {{ number_format($detail->subtotal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <td colspan="4" class="text-right">TOTAL BELANJA:</td>
                                    <td class="text-center text-danger font-weight-bold">
                                        -{{ number_format($penjualan->details->sum('jumlah_jual'), 0, ',', '.') }} pcs
                                    </td>
                                    <td class="text-right text-primary" style="font-size: 16px;">
                                        Rp. {{ number_format($penjualan->total_bayar, 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-right">TUNAI DITERIMA:</td>
                                    <td class="text-right font-weight-bold">Rp. {{ number_format($penjualan->bayar, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-right text-success">KEMBALIAN:</td>
                                    <td class="text-right text-success font-weight-bold" style="font-size: 16px;">
                                        Rp. {{ number_format($penjualan->kembalian, 0, ',', '.') }}
                                    </td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="mt-4 d-flex justify-content-between">
                            <a href="{{ route('account.penjualan.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Kembali ke Riwayat Penjualan
                            </a>
                            <a href="{{ route('account.penjualan.struk', $penjualan->no_faktur) }}" target="_blank" class="btn btn-dark">
                                <i class="fa fa-print"></i> Cetak Struk Kasir
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@stop
