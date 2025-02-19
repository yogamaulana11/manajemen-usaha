@extends('layouts.account')

@section('title')
    Tambah Stok Barang - {{ config('app.name') }}
@stop

@section('content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>STOK BARANG</h1>
            </div>

            <div class="section-body">

                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-money-check-alt"></i> TAMBAH STOK BARANG</h4>
                    </div>

                    <div class="card-body">

                        <form action="{{ route('account.stock.store') }}" method="POST">
                            @csrf

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>NAMA BARANG</label>
                                        <input type="text" name="nama_barang" value="{{ old('nama_barang') }}" placeholder="Masukkan Nama barang" class="form-control">

                                        @error('nama_barang')
                                        <div class="invalid-feedback" style="display: block">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>TANGGAL UPDATE</label>
                                        <input type="text" class="form-control datetimepicker" name="tanggal_update" placeholder="Pilih Tanggal">

                                        @error('tanggal_update')
                                        <div class="invalid-feedback" style="display: block">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>KATEGORI</label>
                                        <input type="text" name="kategori_barang" value="{{ old('kategori_barang') }}" placeholder="Masukkan kategori" class="form-control">
                                        {{-- <select class="form-control select2" name="category_id" style="width: 100%">
                                            <option value="">-- PILIH KATEGORI --</option>
                                            @foreach ($categories as $hasil)
                                                <option value="{{ $hasil->id }}"> {{ $hasil->name }}</option>
                                            @endforeach
                                        </select> --}}

                                        @error('kategori_barang')
                                        <div class="invalid-feedback" style="display: block">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>JUMLAH STOK</label>
                                        <input type="text" name="jumlah_stok" value="{{ old('jumlah_stok') }}" placeholder="Masukkan Jumlah stok" class="form-control">

                                        @error('jumlah_stok')
                                        <div class="invalid-feedback" style="display: block">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <button class="btn btn-primary mr-1 btn-submit" type="submit"><i class="fa fa-paper-plane"></i> SIMPAN</button>
                            <button class="btn btn-warning btn-reset" type="reset"><i class="fa fa-redo"></i> RESET</button>

                        </form>

                    </div>
                </div>
            </div>
        </section>
    </div>
    <script>

        if($(".datetimepicker").length) {
            $('.datetimepicker').daterangepicker({
                locale: {format: 'YYYY-MM-DD hh:mm'},
                singleDatePicker: true,
                timePicker: true,
                timePicker24Hour: true,
            });
        }

        var cleaveC = new Cleave('.currency', {
            numeral: true,
            numeralThousandsGroupStyle: 'thousand'
        });

        var timeoutHandler = null;

        /**
         * btn submit loader
         */
        $( ".btn-submit" ).click(function()
        {
            $( ".btn-submit" ).addClass('btn-progress');
            if (timeoutHandler) clearTimeout(timeoutHandler);

            timeoutHandler = setTimeout(function()
            {
                $( ".btn-submit" ).removeClass('btn-progress');

            }, 1000);
        });

        /**
         * btn reset loader
         */
        $( ".btn-reset" ).click(function()
        {
            $( ".btn-reset" ).addClass('btn-progress');
            if (timeoutHandler) clearTimeout(timeoutHandler);

            timeoutHandler = setTimeout(function()
            {
                $( ".btn-reset" ).removeClass('btn-progress');

            }, 500);
        })

    </script>
@stop
