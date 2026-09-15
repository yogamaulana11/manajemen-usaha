@extends('layouts.account')

@section('title')
Edit Master Pempek - {{ config('app.name') }}
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
                    <h4><i class="fas fa-pencil-alt"></i> EDIT PRODUK PEMPEK</h4>
                </div>

                <div class="card-body">
                    <form action="{{ route('account.master_pempek.update', $pempek->kode_pempek) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>KODE PEMPEK</label>
                                    <input type="text" value="{{ $pempek->kode_pempek }}" class="form-control" readonly disabled>
                                    <small class="form-text text-muted">Kode pempek tidak dapat diubah (Primary Key).</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>NAMA PEMPEK <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_pempek" value="{{ old('nama_pempek', $pempek->nama_pempek) }}"
                                        placeholder="Contoh: Pempek Lenjer Besar / Kapal Selam" class="form-control">

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
                                    <input type="text" name="jenis_ikan" value="{{ old('jenis_ikan', $pempek->jenis_ikan) }}"
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
                                        <input type="text" name="harga" value="{{ old('harga', number_format($pempek->harga, 0)) }}"
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
                                    <label>GANTI FOTO PRODUK</label>
                                    <input type="file" name="foto" class="form-control-file" id="fotoInput" accept="image/*">
                                    <small class="form-text text-muted">Biarkan kosong jika tidak ingin mengubah foto. Format: JPG, JPEG, PNG (Maks 2MB).</small>

                                    @error('foto')
                                    <div class="invalid-feedback" style="display: block">
                                        {{ $message }}
                                    </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>FOTO SAAT INI / PREVIEW</label>
                                    <div>
                                        @if($pempek->foto && file_exists(storage_path('app/public/pempek/' . $pempek->foto)))
                                        <img id="fotoPreview" src="{{ asset('storage/pempek/' . $pempek->foto) }}" alt="{{ $pempek->nama_pempek }}"
                                            style="max-width: 140px; height: 140px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; padding: 4px;">
                                        @else
                                        <img id="fotoPreview" src="#" alt="Preview Foto"
                                            style="display: none; max-width: 140px; height: 140px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; padding: 4px;">
                                        <span id="noPreviewText" class="text-muted"><i class="fas fa-image"></i> Belum ada foto</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-light border mt-2">
                            <i class="fas fa-cubes text-primary"></i>
                            <strong>Stok Saat Ini:</strong> <strong>{{ $pempek->stok }} pcs</strong>.
                            Sesuai aturan bisnis, kuantitas stok dikelola otomatis oleh modul <em>Produksi (Stock In)</em> dan <em>Kasir Penjualan (Stock Out)</em>.
                        </div>

                        <div class="mt-4">
                            <button class="btn btn-primary mr-1 btn-submit" type="submit"><i class="fa fa-paper-plane"></i> PERBARUI</button>
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
            var noText = document.getElementById('noPreviewText');
            if (noText) noText.style.display = 'none';
        }
    };
</script>
@stop