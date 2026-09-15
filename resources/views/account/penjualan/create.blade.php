@extends('layouts.account')

@section('title')
    Kasir Penjualan (POS) - {{ config('app.name') }}
@stop

@section('content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>KASIR PENJUALAN (POINT OF SALE)</h1>
            </div>

            <div class="section-body">
                <div class="row">
                    {{-- Kolom Kiri: Pemilihan Produk & Transaksi Info --}}
                    <div class="col-lg-7">
                        <div class="card card-primary">
                            <div class="card-header bg-white">
                                <h4><i class="fas fa-cart-plus text-primary"></i> PILIH VARIAN PEMPEK</h4>
                            </div>
                            <div class="card-body pb-2">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="font-weight-bold">NO. NOTA</label>
                                        <input type="text" id="noFakturDisplay" value="{{ $autoNoFaktur }}" class="form-control font-weight-bold bg-light" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="font-weight-bold">WAKTU TRANSAKSI</label>
                                        <input type="text" id="inputTanggal" class="form-control datetimepicker" value="{{ date('Y-m-d H:i') }}" required>
                                    </div>
                                </div>

                                {{-- Auto Lookup Form --}}
                                <div class="p-3 bg-light rounded border mb-3">
                                    <div class="form-group mb-2">
                                        <label class="font-weight-bold"><i class="fas fa-search"></i> CARI / PILIH PEMPEK</label>
                                        <select id="selectPempek" class="form-control select2" style="width: 100%;">
                                            <option value="">-- Ketik nama atau pilih produk pempek --</option>
                                            @foreach($pempekList as $p)
                                                <option value="{{ $p->kode_pempek }}"
                                                        data-nama="{{ $p->nama_pempek }}"
                                                        data-ikan="{{ $p->jenis_ikan }}"
                                                        data-harga="{{ $p->harga }}"
                                                        data-stok="{{ $p->stok }}">
                                                    [{{ $p->kode_pempek }}] {{ $p->nama_pempek }} (Rp. {{ number_format($p->harga, 0, ',', '.') }}) - Stok: {{ $p->stok }} pcs
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Info Box Lookup Terpilih --}}
                                    <div id="lookupInfoBox" class="p-3 bg-white border rounded mt-3" style="display: none;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <span class="badge badge-light border font-weight-bold" id="infoKode"></span>
                                                <h6 class="mb-0 font-weight-bold mt-1 text-dark" id="infoNama"></h6>
                                                <small class="text-muted" id="infoIkan"></small>
                                            </div>
                                            <div class="text-right">
                                                <span class="d-block font-weight-bold text-primary" style="font-size: 18px;" id="infoHarga"></span>
                                                <span class="badge" id="infoStokBadge"></span>
                                            </div>
                                        </div>
                                        <div class="row align-items-end mt-3">
                                            <div class="col-6">
                                                <label class="font-weight-bold mb-1">JUMLAH BELI (PCS)</label>
                                                <input type="number" id="inputQtyBeli" class="form-control font-weight-bold" min="1" value="1">
                                            </div>
                                            <div class="col-6">
                                                <button type="button" id="btnTambahKeranjang" class="btn btn-primary btn-block">
                                                    <i class="fas fa-plus-circle"></i> Tambah (Enter)
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Quick Grid Produk --}}
                                <label class="font-weight-bold text-muted mb-2"><i class="fas fa-th-large"></i> KATALOG CEPAT (KLIK UNTUK TAMBAH)</label>
                                <div class="row" style="max-height: 280px; overflow-y: auto;">
                                    @foreach($pempekList as $p)
                                        <div class="col-md-6 col-sm-6 mb-2">
                                            <div class="card card-hover mb-1 border product-card p-2 cursor-pointer {{ $p->stok <= 0 ? 'bg-light text-muted' : '' }}"
                                                 data-kode="{{ $p->kode_pempek }}"
                                                 data-nama="{{ $p->nama_pempek }}"
                                                 data-ikan="{{ $p->jenis_ikan }}"
                                                 data-harga="{{ $p->harga }}"
                                                 data-stok="{{ $p->stok }}"
                                                 style="cursor: pointer; transition: transform .1s;">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong class="d-block text-truncate" style="max-width: 140px;">{{ $p->nama_pempek }}</strong>
                                                        <span class="text-primary font-weight-bold">Rp. {{ number_format($p->harga, 0, ',', '.') }}</span>
                                                    </div>
                                                    <div class="text-right">
                                                        @if($p->stok > 0)
                                                            <span class="badge badge-success">{{ $p->stok }} pcs</span>
                                                        @else
                                                            <span class="badge badge-danger">Habis</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Kolom Kanan: Keranjang Belanja & Panel Pembayaran --}}
                    <div class="col-lg-5">
                        <div class="card card-success">
                            <div class="card-header bg-white d-flex justify-content-between">
                                <h4><i class="fas fa-shopping-basket text-success"></i> KERANJANG BELANJA</h4>
                                <button type="button" id="btnResetKeranjang" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-trash-alt"></i> Reset
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 260px; overflow-y: auto;">
                                    <table class="table table-sm table-striped mb-0">
                                        <thead class="thead-light">
                                        <tr>
                                            <th>ITEM</th>
                                            <th style="width: 30%; text-align: center;">QTY</th>
                                            <th style="width: 25%; text-align: right;">SUBTOTAL</th>
                                            <th style="width: 10%;"></th>
                                        </tr>
                                        </thead>
                                        <tbody id="cartTbody">
                                        <tr id="cartEmptyRow">
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                <i class="fas fa-shopping-cart fa-2x mb-2"></i><br>
                                                Keranjang kasir masih kosong.
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Payment Summary --}}
                                <div class="p-3 border-top bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="font-weight-bold text-muted">TOTAL TAGIHAN:</span>
                                        <span class="font-weight-bold text-primary" id="totalTagihanDisplay" style="font-size: 24px;">Rp. 0</span>
                                    </div>

                                    <div class="form-group mb-2">
                                        <label class="font-weight-bold text-dark mb-1">UANG DITERIMA (BAYAR)</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text font-weight-bold">Rp.</span>
                                            </div>
                                            <input type="text" id="inputBayar" class="form-control form-control-lg font-weight-bold currency" placeholder="0">
                                        </div>
                                    </div>

                                    {{-- Quick cash buttons --}}
                                    <div class="mb-3 d-flex flex-wrap gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-quick-cash mr-1 mb-1" id="btnUangPas">Uang Pas</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-quick-cash mr-1 mb-1" data-val="10000">10k</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-quick-cash mr-1 mb-1" data-val="20000">20k</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-quick-cash mr-1 mb-1" data-val="50000">50k</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-quick-cash mr-1 mb-1" data-val="100000">100k</button>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center p-2 rounded border bg-white mb-3">
                                        <span class="font-weight-bold">UANG KEMBALIAN:</span>
                                        <span class="font-weight-bold text-success" id="kembalianDisplay" style="font-size: 20px;">Rp. 0</span>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label class="small text-muted font-weight-bold mb-1">CATATAN PESANAN (OPSIONAL)</label>
                                        <input type="text" id="inputCatatan" class="form-control form-control-sm" placeholder="Contoh: Kuah cuko dipisah / Pedas">
                                    </div>

                                    <button type="button" id="btnProsesBayar" class="btn btn-success btn-lg btn-block font-weight-bold py-3">
                                        <i class="fas fa-check-circle"></i> PROSES TRANSAKSI & BAYAR
                                    </button>
                                </div>
                            </div>
                        </div>
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

            var cleaveBayar = new Cleave('#inputBayar', {
                numeral: true,
                numeralThousandsGroupStyle: 'thousand'
            });

            // Cart Data Store: { kode: { kode, nama, harga, stok, qty } }
            var cart = {};

            function formatRupiah(num) {
                return 'Rp. ' + (num || 0).toLocaleString('id-ID');
            }

            function getCleanBayar() {
                var val = $('#inputBayar').val();
                return parseInt(val.replace(/\./g, '').replace(/,/g, '')) || 0;
            }

            function calculateTotals() {
                var total = 0;
                var totalItemCount = 0;

                for (var kode in cart) {
                    var item = cart[kode];
                    total += item.harga * item.qty;
                    totalItemCount += item.qty;
                }

                $('#totalTagihanDisplay').text(formatRupiah(total));

                var bayar = getCleanBayar();
                var kembalian = bayar - total;

                if (kembalian >= 0) {
                    $('#kembalianDisplay').removeClass('text-danger').addClass('text-success').text(formatRupiah(kembalian));
                } else {
                    $('#kembalianDisplay').removeClass('text-success').addClass('text-danger').text('- ' + formatRupiah(Math.abs(kembalian)));
                }

                return total;
            }

            function renderCart() {
                var keys = Object.keys(cart);
                var tbody = $('#cartTbody');
                tbody.empty();

                if (keys.length === 0) {
                    tbody.append(`
                        <tr id="cartEmptyRow">
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="fas fa-shopping-cart fa-2x mb-2"></i><br>
                                Keranjang kasir masih kosong.
                            </td>
                        </tr>
                    `);
                } else {
                    keys.forEach(function(kode) {
                        var item = cart[kode];
                        var subtotal = item.harga * item.qty;

                        var tr = `
                            <tr id="cart-item-${item.kode}">
                                <td>
                                    <strong class="d-block">${item.nama}</strong>
                                    <small class="text-muted">${formatRupiah(item.harga)} x ${item.qty}</small>
                                </td>
                                <td class="text-center align-middle">
                                    <div class="input-group input-group-sm" style="width: 100px; margin: auto;">
                                        <div class="input-group-prepend">
                                            <button class="btn btn-outline-secondary btn-minus-qty" data-kode="${item.kode}" type="button">-</button>
                                        </div>
                                        <input type="text" class="form-control text-center font-weight-bold item-qty-input"
                                               data-kode="${item.kode}" value="${item.qty}" readonly>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary btn-plus-qty" data-kode="${item.kode}" type="button">+</button>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-right align-middle font-weight-bold text-primary">
                                    ${formatRupiah(subtotal)}
                                </td>
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-sm btn-link text-danger btn-del-cart" data-kode="${item.kode}">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                        tbody.append(tr);
                    });
                }

                calculateTotals();
            }

            function addToCart(kode, nama, harga, stok, qtyToAdd) {
                if (stok <= 0) {
                    swal({
                        title: 'Stok Habis!',
                        text: 'Stok untuk ' + nama + ' saat ini kosong (0 pcs). Harap lakukan transaksi produksi terlebih dahulu.',
                        icon: 'error',
                        button: 'OK'
                    });
                    return;
                }

                var currentQty = cart[kode] ? cart[kode].qty : 0;
                var targetQty = currentQty + qtyToAdd;

                if (targetQty > stok) {
                    swal({
                        title: 'Stok Tidak Cukup!',
                        text: 'Maksimal stok tersedia untuk ' + nama + ' adalah ' + stok + ' pcs (sudah ada ' + currentQty + ' pcs di keranjang).',
                        icon: 'warning',
                        button: 'OK'
                    });
                    return;
                }

                cart[kode] = {
                    kode: kode,
                    nama: nama,
                    harga: harga,
                    stok: stok,
                    qty: targetQty
                };

                renderCart();
            }

            // Event Select Lookup Pempek
            $('#selectPempek').on('change', function() {
                var kode = $(this).val();
                if (!kode) {
                    $('#lookupInfoBox').hide();
                    return;
                }
                var opt = $(this).find('option:selected');
                var nama = opt.data('nama');
                var ikan = opt.data('ikan');
                var harga = parseFloat(opt.data('harga'));
                var stok = parseInt(opt.data('stok'));

                $('#infoKode').text(kode);
                $('#infoNama').text(nama);
                $('#infoIkan').text('Bahan Ikan: ' + ikan);
                $('#infoHarga').text(formatRupiah(harga));

                var stokBadge = $('#infoStokBadge');
                if (stok > 10) {
                    stokBadge.attr('class', 'badge badge-success').text('Stok: ' + stok + ' pcs');
                } else if (stok > 0) {
                    stokBadge.attr('class', 'badge badge-warning').text('Sisa: ' + stok + ' pcs');
                } else {
                    stokBadge.attr('class', 'badge badge-danger').text('Stok Habis');
                }

                $('#inputQtyBeli').val(1).attr('max', stok);
                $('#lookupInfoBox').show();
                $('#inputQtyBeli').focus().select();
            });

            // Tombol Tambah ke Keranjang dari info box
            $('#btnTambahKeranjang').on('click', function() {
                var opt = $('#selectPempek').find('option:selected');
                if (!opt.val()) return;

                var kode = opt.val();
                var nama = opt.data('nama');
                var harga = parseFloat(opt.data('harga'));
                var stok = parseInt(opt.data('stok'));
                var qty = parseInt($('#inputQtyBeli').val()) || 1;

                addToCart(kode, nama, harga, stok, qty);
                $('#selectPempek').val('').trigger('change');
            });

            $('#inputQtyBeli').on('keydown', function(e) {
                if (e.key === 'Enter' || e.keyCode === 13) {
                    e.preventDefault();
                    $('#btnTambahKeranjang').click();
                }
            });

            // Klik Card Produk
            $('.product-card').on('click', function() {
                var kode = $(this).data('kode');
                var nama = $(this).data('nama');
                var harga = parseFloat($(this).data('harga'));
                var stok = parseInt($(this).data('stok'));
                addToCart(kode, nama, harga, stok, 1);
            });

            // Cart Item Qty Buttons (+ / -)
            $(document).on('click', '.btn-plus-qty', function() {
                var kode = $(this).data('kode');
                if (cart[kode]) {
                    addToCart(kode, cart[kode].nama, cart[kode].harga, cart[kode].stok, 1);
                }
            });

            $(document).on('click', '.btn-minus-qty', function() {
                var kode = $(this).data('kode');
                if (cart[kode]) {
                    if (cart[kode].qty > 1) {
                        cart[kode].qty -= 1;
                        renderCart();
                    } else {
                        delete cart[kode];
                        renderCart();
                    }
                }
            });

            $(document).on('click', '.btn-del-cart', function() {
                var kode = $(this).data('kode');
                delete cart[kode];
                renderCart();
            });

            $('#btnResetKeranjang').on('click', function() {
                if (Object.keys(cart).length === 0) return;
                swal({
                    title: 'Kosongkan Keranjang?',
                    text: 'Seluruh item dalam keranjang kasir akan dihapus.',
                    icon: 'warning',
                    buttons: ['Batal', 'Ya, Kosongkan'],
                    dangerMode: true,
                }).then(function(ok) {
                    if (ok) {
                        cart = {};
                        renderCart();
                    }
                });
            });

            // Quick cash buttons
            $('.btn-quick-cash').on('click', function() {
                var addVal = parseInt($(this).data('val'));
                if (addVal) {
                    $('#inputBayar').val(addVal.toLocaleString('id-ID'));
                }
                calculateTotals();
            });

            $('#btnUangPas').on('click', function() {
                var total = calculateTotals();
                $('#inputBayar').val(total.toLocaleString('id-ID'));
                calculateTotals();
            });

            $('#inputBayar').on('keyup input', function() {
                calculateTotals();
            });

            // Proses Transaksi
            $('#btnProsesBayar').on('click', function() {
                var keys = Object.keys(cart);
                if (keys.length === 0) {
                    swal({
                        title: 'Keranjang Kosong!',
                        text: 'Tambahkan minimal 1 varian pempek ke dalam keranjang kasir.',
                        icon: 'error',
                        button: 'OK'
                    });
                    return;
                }

                var total = calculateTotals();
                var bayar = getCleanBayar();

                if (bayar < total) {
                    swal({
                        title: 'Pembayaran Kurang!',
                        text: 'Uang pembayaran (' + formatRupiah(bayar) + ') kurang dari total tagihan (' + formatRupiah(total) + ')!',
                        icon: 'warning',
                        button: 'OK'
                    });
                    $('#inputBayar').focus();
                    return;
                }

                var itemsPayload = [];
                keys.forEach(function(kode) {
                    itemsPayload.push({
                        kode_pempek: kode,
                        jumlah_jual: cart[kode].qty
                    });
                });

                var payload = {
                    _token: $("meta[name='csrf-token']").attr("content"),
                    tanggal_jual: $('#inputTanggal').val(),
                    bayar: bayar,
                    catatan: $('#inputCatatan').val(),
                    items: itemsPayload
                };

                $('#btnProsesBayar').addClass('btn-progress');

                $.ajax({
                    url: "{{ route('account.penjualan.store') }}",
                    type: "POST",
                    data: payload,
                    success: function(res) {
                        $('#btnProsesBayar').removeClass('btn-progress');
                        if (res.status === 'success') {
                            swal({
                                title: 'TRANSAKSI BERHASIL!',
                                text: 'Nota: ' + res.no_faktur + '\nTotal: ' + formatRupiah(res.total_bayar) + '\nKembalian: ' + formatRupiah(res.kembalian),
                                icon: 'success',
                                buttons: {
                                    struk: {
                                        text: "Cetak Struk",
                                        value: "struk",
                                    },
                                    confirm: {
                                        text: "Transaksi Baru",
                                        value: "baru",
                                    }
                                }
                            }).then(function(value) {
                                if (value === 'struk') {
                                    window.open(res.struk_url, '_blank');
                                    location.reload();
                                } else {
                                    location.reload();
                                }
                            });
                        }
                    },
                    error: function(xhr) {
                        $('#btnProsesBayar').removeClass('btn-progress');
                        var msg = 'Terjadi kesalahan pada sistem.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        swal({
                            title: 'Transaksi Gagal!',
                            text: msg,
                            icon: 'error',
                            button: 'Tutup'
                        });
                    }
                });
            });
        });
    </script>
@stop
