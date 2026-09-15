@extends('layouts.account')

@section('title')
Riwayat Penjualan Kasir - {{ config('app.name') }}
@stop

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>TRANSAKSI KASIR PENJUALAN (STOCK OUT)</h1>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-cash-register"></i> RIWAYAT NOTA PENJUALAN</h4>
                </div>

                <div class="card-body">
                    <form action="{{ route('account.penjualan.search') }}" method="GET">
                        <div class="form-group">
                            <div class="input-group mb-3">
                                <!-- <div class="input-group-prepend">
                                        <a href="{{ route('account.penjualan.create') }}" class="btn btn-primary" style="padding-top: 10px;">
                                            <i class="fa fa-shopping-cart"></i> BUKA KASIR PENJUALAN (POS)
                                        </a>
                                    </div> -->
                                <input type="text" class="form-control" name="q" value="{{ request()->get('q') }}"
                                    placeholder="Cari berdasarkan nomor nota/faktur atau catatan pelanggan...">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> CARI</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th scope="col" style="text-align: center; width: 6%">NO.</th>
                                    <th scope="col" style="width: 18%">NO. NOTA</th>
                                    <th scope="col" style="width: 17%">WAKTU TRANSAKSI</th>
                                    <th scope="col" style="text-align: center; width: 12%">ITEM TERJUAL</th>
                                    <th scope="col" style="width: 15%">TOTAL BELANJA</th>
                                    <th scope="col" style="width: 12%">PEMBAYARAN</th>
                                    <th scope="col">CATATAN</th>
                                    <th scope="col" style="width: 15%; text-align: center">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($penjualan as $no => $item)
                                @php
                                $totalQty = $item->details->sum('jumlah_jual');
                                @endphp
                                <tr>
                                    <th scope="row" style="text-align: center">
                                        {{ ++$no + ($penjualan->currentPage() - 1) * $penjualan->perPage() }}
                                    </th>
                                    <td>
                                        <span class="badge badge-success font-weight-bold" style="font-size: 13px;">
                                            <i class="fas fa-receipt"></i> {{ $item->no_faktur }}
                                        </span>
                                    </td>
                                    <td>{{ date('d M Y, H:i', strtotime($item->tanggal_jual)) }}</td>
                                    <td class="text-center font-weight-bold">
                                        <span class="badge badge-light border">{{ $totalQty }} pcs</span>
                                    </td>
                                    <td class="font-weight-bold text-primary">
                                        Rp. {{ number_format($item->total_bayar, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        <small class="d-block text-muted">Bayar: Rp. {{ number_format($item->bayar, 0, ',', '.') }}</small>
                                        <small class="d-block text-success font-weight-bold">Kembali: Rp. {{ number_format($item->kembalian, 0, ',', '.') }}</small>
                                    </td>
                                    <td>{{ $item->catatan ?: '-' }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('account.penjualan.show', $item->no_faktur) }}"
                                            class="btn btn-sm btn-info" title="Lihat Rincian Item">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="{{ route('account.penjualan.struk', $item->no_faktur) }}" target="_blank"
                                            class="btn btn-sm btn-dark" title="Cetak Struk Nota">
                                            <i class="fa fa-print"></i> STRUK
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fas fa-shopping-basket fa-2x mb-2"></i><br>
                                        Belum ada catatan transaksi kasir penjualan. Silakan klik tombol <strong>Buka Kasir Penjualan</strong> di atas.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <div class="d-flex justify-content-center">
                            {{$penjualan->links("vendor.pagination.bootstrap-4")}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    @if($message = Session::get('success'))
    swal({
        type: "success",
        icon: "success",
        title: "BERHASIL!",
        text: "{{ $message }}",
        timer: 2000,
        showConfirmButton: false,
        buttons: false,
    });
    @elseif($message = Session::get('error'))
    swal({
        type: "error",
        icon: "error",
        title: "GAGAL!",
        text: "{{ $message }}",
        timer: 3000,
        showConfirmButton: true,
    });
    @endif
</script>
@stop