@extends('layouts.account')

@section('title')
    Input Transaksi Produksi - {{ config('app.name') }}
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
                        <h4><i class="fas fa-file-invoice"></i> FORM FAKTUR PRODUKSI PEMPEK (MULTI-ITEM)</h4>
                    </div>

                    <div class="card-body">
                        <form id="formProduksi" action="{{ route('account.produksi.store') }}" method="POST">
                            @csrf

                            {{-- Header Transaksi --}}
                            <div class="card border mb-4 bg-light">
                                <div class="card-body">
                                    <h6 class="text-primary font-weight-bold mb-3"><i class="fas fa-info-circle"></i> DATA HEADER FAKTUR PRODUKSI</h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group mb-2">
                                                <label>NO. FAKTUR</label>
                                                <input type="text" name="no_faktur" value="{{ $autoNoFaktur }}" class="form-control font-weight-bold" readonly>
                                                <small class="text-muted">Nomor faktur digenerate otomatis oleh sistem.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-2">
                                                <label>TANGGAL PRODUKSI <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control datetimepicker" name="tanggal"
                                                       value="{{ old('tanggal', date('Y-m-d H:i')) }}" required>
                                                @error('tanggal')
                                                <div class="invalid-feedback" style="display: block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-2">
                                                <label>KETERANGAN OPERASIONAL</label>
                                                <input type="text" class="form-control" name="keterangan"
                                                       value="{{ old('keterangan') }}" placeholder="Contoh: Produksi Dapur Pagi / Pesanan Khusus">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Input Baris Barang Dinamis --}}
                            <div class="card border mb-4">
                                <div class="card-header bg-white">
                                    <h6 class="mb-0 text-dark font-weight-bold"><i class="fas fa-plus-square text-success"></i> TAMBAHKAN ITEM PRODUKSI KE FAKTUR</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row align-items-end">
                                        <div class="col-md-6">
                                            <div class="form-group mb-md-0">
                                                <label>PILIH VARIAN PEMPEK</label>
                                                <select id="inputProduk" class="form-control select2" style="width: 100%;">
                                                    <option value="">-- Pilih Varian Produk Pempek --</option>
                                                    @foreach($pempekList as $p)
                                                        <option value="{{ $p->kode_pempek }}" data-nama="{{ $p->nama_pempek }}" data-ikan="{{ $p->jenis_ikan }}" data-stok="{{ $p->stok }}">
                                                            [{{ $p->kode_pempek }}] {{ $p->nama_pempek }} (Bahan: {{ $p->jenis_ikan }} | Stok saat ini: {{ $p->stok }} pcs)
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group mb-md-0">
                                                <label>JUMLAH PRODUKSI (PCS)</label>
                                                <input type="number" id="inputJumlah" class="form-control" min="1" placeholder="Misal: 50">
                                                <small class="text-muted">Tekan <strong>Enter</strong> untuk menambahkan</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="button" id="btnTambahBaris" class="btn btn-success btn-block py-2">
                                                <i class="fas fa-plus"></i> Tambah ke Faktur (Enter)
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Tabel Detail Faktur --}}
                            <h6 class="font-weight-bold mb-3"><i class="fas fa-list-ul"></i> RINCIAN BARANG PADA FAKTUR INI</h6>
                            @error('items')
                            <div class="alert alert-danger">{{ $message }}</div>
                            @enderror

                            <div class="table-responsive mb-4">
                                <table class="table table-bordered table-striped" id="tabelBarangProduksi">
                                    <thead class="thead-light">
                                    <tr>
                                        <th style="width: 5%; text-align: center">NO.</th>
                                        <th style="width: 20%">KODE PEMPEK</th>
                                        <th>NAMA PEMPEK</th>
                                        <th style="width: 15%">BAHAN IKAN</th>
                                        <th style="width: 15%; text-align: center">STOK TERAKHIR</th>
                                        <th style="width: 18%; text-align: center">JUMLAH PRODUKSI (+)</th>
                                        <th style="width: 10%; text-align: center">AKSI</th>
                                    </tr>
                                    </thead>
                                    <tbody id="tbodyDetail">
                                    <tr id="rowEmpty">
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fas fa-arrow-up"></i> Belum ada item yang ditambahkan. Silakan pilih produk dan jumlah produksi di atas, lalu tekan <strong>Enter</strong>.
                                        </td>
                                    </tr>
                                    </tbody>
                                    <tfoot class="bg-light font-weight-bold">
                                    <tr>
                                        <td colspan="5" class="text-right">TOTAL KUANTITAS PRODUKSI:</td>
                                        <td class="text-center text-success" id="totalQtyBadge" style="font-size: 16px;">0 pcs</td>
                                        <td></td>
                                    </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <a href="{{ route('account.produksi.index') }}" class="btn btn-secondary">
                                    <i class="fa fa-arrow-left"></i> KEMBALI KE RIWAYAT
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg px-4" id="btnSimpanFaktur">
                                    <i class="fa fa-save"></i> SIMPAN FAKTUR PRODUKSI
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        $(document).ready(function() {
            if($(".datetimepicker").length) {
                $('.datetimepicker').daterangepicker({
                    locale: {format: 'YYYY-MM-DD HH:mm'},
                    singleDatePicker: true,
                    timePicker: true,
                    timePicker24Hour: true,
                });
            }

            var itemIndex = 0;
            var itemsList = {}; // map kode_pempek -> index

            function recalculateTotals() {
                var total = 0;
                var count = 0;
                $('.qty-input-hidden').each(function() {
                    total += parseInt($(this).val()) || 0;
                    count++;
                });

                $('#totalQtyBadge').text(total.toLocaleString('id-ID') + ' pcs');

                if (count === 0) {
                    $('#rowEmpty').show();
                } else {
                    $('#rowEmpty').hide();
                }
            }

            function appendOrUpdateRow() {
                var kode = $('#inputProduk').val();
                var jumlah = parseInt($('#inputJumlah').val());

                if (!kode) {
                    swal({
                        title: 'Peringatan',
                        text: 'Silakan pilih varian pempek terlebih dahulu!',
                        icon: 'warning',
                        button: 'OK'
                    });
                    $('#inputProduk').select2('open');
                    return;
                }

                if (!jumlah || jumlah <= 0) {
                    swal({
                        title: 'Peringatan',
                        text: 'Jumlah produksi harus minimal 1!',
                        icon: 'warning',
                        button: 'OK'
                    });
                    $('#inputJumlah').focus();
                    return;
                }

                var optionSelected = $('#inputProduk').find('option:selected');
                var nama = optionSelected.data('nama');
                var ikan = optionSelected.data('ikan');
                var stokLama = optionSelected.data('stok');

                // Jika sudah ada baris barang ini, tambahkan jumlahnya
                if (itemsList[kode] !== undefined) {
                    var existingIdx = itemsList[kode];
                    var currentInput = $('#row-' + existingIdx).find('.qty-input-hidden');
                    var newQty = parseInt(currentInput.val()) + jumlah;
                    currentInput.val(newQty);
                    $('#row-' + existingIdx).find('.qty-display').text(newQty.toLocaleString('id-ID') + ' pcs');
                } else {
                    var idx = itemIndex++;
                    itemsList[kode] = idx;

                    var rowHtml = `
                        <tr id="row-${idx}" class="item-row">
                            <td class="text-center row-number font-weight-bold"></td>
                            <td>
                                <input type="hidden" name="items[${idx}][kode_pempek]" value="${kode}">
                                <span class="badge badge-light border font-weight-bold">${kode}</span>
                            </td>
                            <td class="font-weight-600">${nama}</td>
                            <td><span class="badge badge-info">${ikan}</span></td>
                            <td class="text-center">${stokLama} pcs</td>
                            <td class="text-center font-weight-bold text-success">
                                <input type="hidden" name="items[${idx}][jumlah_produksi]" value="${jumlah}" class="qty-input-hidden">
                                <span class="qty-display">+${jumlah.toLocaleString('id-ID')} pcs</span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger btn-hapus-row" data-kode="${kode}" data-idx="${idx}">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;

                    $('#tbodyDetail').append(rowHtml);
                }

                // Update nomor baris
                updateRowNumbers();
                recalculateTotals();

                // Reset input & focus balik ke produk untuk batch input cepat
                $('#inputJumlah').val('');
                $('#inputProduk').val('').trigger('change');
                $('#inputProduk').select2('open');
            }

            function updateRowNumbers() {
                var num = 1;
                $('#tbodyDetail tr.item-row').each(function() {
                    $(this).find('.row-number').text(num++);
                });
            }

            $('#btnTambahBaris').on('click', function(e) {
                e.preventDefault();
                appendOrUpdateRow();
            });

            // Enter key on input jumlah triggers append row
            $('#inputJumlah').on('keydown', function(e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault();
                    appendOrUpdateRow();
                }
            });

            // Hapus baris
            $(document).on('click', '.btn-hapus-row', function() {
                var kode = $(this).data('kode');
                var idx = $(this).data('idx');
                $('#row-' + idx).remove();
                delete itemsList[kode];
                updateRowNumbers();
                recalculateTotals();
            });

            // Validasi submit form
            $('#formProduksi').on('submit', function(e) {
                var count = $('.qty-input-hidden').length;
                if (count === 0) {
                    e.preventDefault();
                    swal({
                        title: 'Faktur Masih Kosong!',
                        text: 'Harap tambahkan minimal 1 item pempek ke dalam faktur sebelum menyimpan.',
                        icon: 'error',
                        button: 'OK'
                    });
                    return false;
                }
                $('#btnSimpanFaktur').addClass('btn-progress');
            });
        });
    </script>
@stop
