@extends('layouts.account')

@section('title')
Tambah Master Pempek - {{ config('app.name') }}
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
                    <h4><i class="fas fa-plus-circle"></i> TAMBAH PRODUK PEMPEK</h4>
                </div>

                <div class="card-body">
                    <form action="{{ route('account.master_pempek.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>KODE PEMPEK <span class="text-danger">*</span></label>
                                    <input type="text" name="kode_pempek" value="{{ old('kode_pempek', $autoCode) }}"
                                        placeholder="Contoh: PMP-001" class="form-control" style="text-transform: uppercase;">
                                    <small class="form-text text-muted">Kode unik identifikasi produk (Primary Key).</small>

                                    @error('kode_pempek')
                                    <div class="invalid-feedback" style="display: block">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>NAMA PEMPEK <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_pempek" value="{{ old('nama_pempek') }}"
                                        placeholder="Contoh: Pempek Lenjer Besar / Kapal Selam" class="form-control">
                                    <!-- <select name="nama_pempek" id="nama_pempek" class="form-control" value="{{ old('nama_pempek') }}">
                                        <option value="">Pilih Nama Pempek</option>
                                        @foreach($stockBarang as $pempek)
                                        <option value="{{ $pempek->nama_barang }}">{{ $pempek->nama_barang }}</option>
                                        @endforeach
                                    </select> -->

                                    @error('nama_pempek')
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
                                    <label>JENIS BAHAN BAKU IKAN <span class="text-danger">*</span></label>
                                    <input type="text" name="jenis_ikan" value="{{ old('jenis_ikan', 'Tenggiri') }}"
                                        placeholder="Contoh: Tenggiri, Gabus, Kakap" class="form-control">

                                    @error('jenis_ikan')
                                    <div class="invalid-feedback" style="display: block">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>HARGA JUAL SATUAN (RP) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp.</span>
                                        </div>
                                        <input type="text" name="harga" value="{{ old('harga') }}"
                                            placeholder="Masukkan harga satuan" class="form-control currency">
                                    </div>

                                    @error('harga')
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
                                    <label>FOTO PRODUK</label>
                                    <input type="file" name="foto" class="form-control-file" id="fotoInput" accept="image/*">
                                    <small class="form-text text-muted">Format: JPG, JPEG, PNG (Maksimal 2MB).</small>

                                    @error('foto')
                                    <div class="invalid-feedback" style="display: block">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>PREVIEW FOTO</label>
                                    <div>
                                        <img id="fotoPreview" src="#" alt="Preview Foto"
                                            style="display: none; max-width: 140px; height: 140px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; padding: 4px;">
                                        <span id="noPreviewText" class="text-muted"><i class="fas fa-image"></i> Belum ada foto yang dipilih</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-light border mt-2">
                            <i class="fas fa-info-circle text-info"></i>
                            <strong>Catatan Stok:</strong> Stok awal produk baru adalah <strong>0</strong>. Stok akan bertambah secara otomatis melalui modul <strong>Produksi Pempek (Stock In)</strong>.
                        </div>

                        <div class="mt-4">
                            <button class="btn btn-primary mr-1 btn-submit" type="submit"><i class="fa fa-paper-plane"></i> SIMPAN</button>
                            <a href="{{ route('account.master_pempek.index') }}" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> KEMBALI</a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    var cleaveC = new Cleave('.currency', {
        numeral: true,
        numeralThousandsGroupStyle: 'thousand'
    });

    document.getElementById('fotoInput').onchange = function(evt) {
        var [file] = this.files;
        if (file) {
            var preview = document.getElementById('fotoPreview');
            preview.src = URL.createObjectURL(file);
            preview.style.display = 'block';
            document.getElementById('noPreviewText').style.display = 'none';
        }
    };
</script>
@stop