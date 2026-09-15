@extends('layouts.account')

@section('title')
    Master Pempek - {{ config('app.name') }}
@stop

@section('content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>MASTER DATA PEMPEK</h1>
            </div>

            <div class="section-body">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-utensils"></i> DAFTAR PRODUK PEMPEK</h4>
                    </div>

                    <div class="card-body">
                        <form action="{{ route('account.master_pempek.search') }}" method="GET">
                            <div class="form-group">
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <a href="{{ route('account.master_pempek.create') }}" class="btn btn-primary" style="padding-top: 10px;">
                                            <i class="fa fa-plus-circle"></i> TAMBAH PEMPEK
                                        </a>
                                    </div>
                                    <input type="text" class="form-control" name="q" value="{{ request()->get('q') }}"
                                           placeholder="Cari berdasarkan kode, nama pempek, atau jenis ikan...">
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
                                    <th scope="col" style="text-align: center; width: 10%">FOTO</th>
                                    <th scope="col" style="width: 14%">KODE</th>
                                    <th scope="col">NAMA PEMPEK</th>
                                    <th scope="col">JENIS IKAN</th>
                                    <th scope="col">HARGA JUAL</th>
                                    <th scope="col" style="text-align: center; width: 12%">STOK TERSEDIA</th>
                                    <th scope="col" style="width: 15%; text-align: center">AKSI</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($pempek as $no => $item)
                                    <tr>
                                        <th scope="row" style="text-align: center">
                                            {{ ++$no + ($pempek->currentPage() - 1) * $pempek->perPage() }}
                                        </th>
                                        <td class="text-center">
                                            @if($item->foto && file_exists(storage_path('app/public/pempek/' . $item->foto)))
                                                <img src="{{ asset('storage/pempek/' . $item->foto) }}" alt="{{ $item->nama_pempek }}"
                                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            @else
                                                <span class="badge badge-secondary py-2"><i class="fas fa-image"></i> No Foto</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-light font-weight-bold" style="font-size: 13px;">
                                                {{ $item->kode_pempek }}
                                            </span>
                                        </td>
                                        <td class="font-weight-600">{{ $item->nama_pempek }}</td>
                                        <td><span class="badge badge-info">{{ $item->jenis_ikan }}</span></td>
                                        <td>Rp. {{ number_format($item->harga, 0, ',', '.') }}</td>
                                        <td class="text-center">
                                            @if($item->stok > 10)
                                                <span class="badge badge-success px-3 py-1 font-weight-bold" style="font-size: 13px;">
                                                    {{ $item->stok }} pcs
                                                </span>
                                            @elseif($item->stok > 0)
                                                <span class="badge badge-warning px-3 py-1 font-weight-bold" style="font-size: 13px;">
                                                    {{ $item->stok }} pcs
                                                </span>
                                            @else
                                                <span class="badge badge-danger px-3 py-1 font-weight-bold" style="font-size: 13px;">
                                                    Habis (0)
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('account.master_pempek.edit', $item->kode_pempek) }}"
                                               class="btn btn-sm btn-primary" title="Edit Data">
                                                <i class="fa fa-pencil-alt"></i>
                                            </a>
                                            <button onClick="Delete('{{ $item->kode_pempek }}')" class="btn btn-sm btn-danger"
                                                    id="{{ $item->kode_pempek }}" title="Hapus Data">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <i class="fas fa-box-open fa-2x mb-2"></i><br>
                                            Belum ada data pempek. Silakan klik tombol <strong>Tambah Pempek</strong> di atas.
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>

                            <div class="d-flex justify-content-center">
                                {{$pempek->links("vendor.pagination.bootstrap-4")}}
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
            timer: 2500,
            showConfirmButton: true,
        });
        @endif

        function Delete(kode_pempek) {
            var token = $("meta[name='csrf-token']").attr("content");

            swal({
                title: "APAKAH ANDA YAKIN?",
                text: "Ingin menghapus data pempek (" + kode_pempek + ") ini?",
                icon: "warning",
                buttons: ['BATAL', 'YA, HAPUS'],
                dangerMode: true,
            }).then(function(isConfirm) {
                if (isConfirm) {
                    jQuery.ajax({
                        url: "{{ route('account.master_pempek.index') }}/" + kode_pempek,
                        data: {
                            "_token": token
                        },
                        type: 'DELETE',
                        success: function (response) {
                            if (response.status == "success") {
                                swal({
                                    title: 'BERHASIL!',
                                    text: response.message || 'Data berhasil dihapus!',
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false,
                                    buttons: false,
                                }).then(function() {
                                    location.reload();
                                });
                            } else {
                                swal({
                                    title: 'GAGAL!',
                                    text: response.message || 'Data gagal dihapus!',
                                    icon: 'error',
                                    showConfirmButton: true,
                                });
                            }
                        },
                        error: function (xhr) {
                            var msg = 'Terjadi kesalahan sistem!';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            swal({
                                title: 'GAGAL!',
                                text: msg,
                                icon: 'error',
                                showConfirmButton: true,
                            });
                        }
                    });
                }
            });
        }
    </script>
@stop
