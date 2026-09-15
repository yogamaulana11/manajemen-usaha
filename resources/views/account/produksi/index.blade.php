@extends('layouts.account')

@section('title')
    Riwayat Produksi Pempek - {{ config('app.name') }}
@stop

@section('content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>TRANSAKSI PRODUKSI (STOCK IN)</h1>
            </div>

            <div class="section-body">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-boxes"></i> DAFTAR FAKTUR PRODUKSI PEMPEK</h4>
                    </div>

                    <div class="card-body">
                        <form action="{{ route('account.produksi.search') }}" method="GET">
                            <div class="form-group">
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <a href="{{ route('account.produksi.create') }}" class="btn btn-primary" style="padding-top: 10px;">
                                            <i class="fa fa-plus-circle"></i> FAKTUR PRODUKSI BARU
                                        </a>
                                    </div>
                                    <input type="text" class="form-control" name="q" value="{{ request()->get('q') }}"
                                           placeholder="Cari berdasarkan nomor faktur atau keterangan...">
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
                                    <th scope="col" style="width: 20%">NO. FAKTUR</th>
                                    <th scope="col" style="width: 18%">TANGGAL PRODUKSI</th>
                                    <th scope="col" style="text-align: center; width: 14%">TOTAL ITEM</th>
                                    <th scope="col" style="text-align: center; width: 15%">TOTAL KUANTITAS</th>
                                    <th scope="col">KETERANGAN</th>
                                    <th scope="col" style="width: 12%; text-align: center">RINCIAN</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($produksi as $no => $item)
                                    @php
                                        $totalQty = $item->details->sum('jumlah_produksi');
                                        $totalVarians = $item->details->count();
                                    @endphp
                                    <tr>
                                        <th scope="row" style="text-align: center">
                                            {{ ++$no + ($produksi->currentPage() - 1) * $produksi->perPage() }}
                                        </th>
                                        <td>
                                            <span class="badge badge-primary font-weight-bold" style="font-size: 13px;">
                                                <i class="fas fa-file-invoice"></i> {{ $item->no_faktur }}
                                            </span>
                                        </td>
                                        <td>{{ date('d M Y, H:i', strtotime($item->tanggal)) }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-light border">{{ $totalVarians }} jenis</span>
                                        </td>
                                        <td class="text-center font-weight-bold text-success">
                                            +{{ number_format($totalQty, 0, ',', '.') }} pcs
                                        </td>
                                        <td>{{ $item->keterangan ?? '-' }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('account.produksi.show', $item->no_faktur) }}"
                                               class="btn btn-sm btn-info" title="Lihat Rincian Faktur">
                                                <i class="fa fa-eye"></i> DETAIL
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fas fa-clipboard-list fa-2x mb-2"></i><br>
                                            Belum ada catatan transaksi produksi. Silakan klik tombol <strong>Faktur Produksi Baru</strong> di atas.
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>

                            <div class="d-flex justify-content-center">
                                {{$produksi->links("vendor.pagination.bootstrap-4")}}
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
