<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran - {{ $penjualan->no_faktur }}</title>
    <style>
        @page {
            margin: 0;
            size: 80mm auto;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 15px;
            width: 76mm;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        .store-title {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 2px 0;
            vertical-align: top;
        }
        .btn-print-box {
            margin-bottom: 15px;
            text-align: center;
        }
        .btn-print {
            padding: 8px 16px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="btn-print-box no-print">
        <button class="btn-print" onclick="window.print()">🖨️ CETAK STRUK</button>
        <button class="btn-print" style="background: #6c757d;" onclick="window.close()">TUTUP</button>
    </div>

    <div class="text-center">
        <div class="store-title">PEMPEK 755</div>
        <div>Jl. Merdeka No. 755, Palembang</div>
        <div>Telp: 0812-3456-7890</div>
    </div>

    <div class="divider"></div>

    <div>
        <div>No. Nota : {{ $penjualan->no_faktur }}</div>
        <div>Tanggal  : {{ date('d/m/Y H:i', strtotime($penjualan->tanggal_jual)) }}</div>
        <div>Kasir    : {{ $penjualan->user ? $penjualan->user->full_name : 'Kasir' }}</div>
        @if($penjualan->catatan)
            <div>Catatan  : {{ $penjualan->catatan }}</div>
        @endif
    </div>

    <div class="divider"></div>

    <table>
        @foreach($penjualan->details as $detail)
            <tr>
                <td colspan="2" class="font-bold">{{ $detail->pempek ? $detail->pempek->nama_pempek : $detail->kode_pempek }}</td>
            </tr>
            <tr>
                <td>{{ $detail->jumlah_jual }} x {{ number_format($detail->harga, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($detail->subtotal, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>

    <div class="divider"></div>

    <table>
        <tr>
            <td class="font-bold">TOTAL BELANJA</td>
            <td class="text-right font-bold">Rp. {{ number_format($penjualan->total_bayar, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>TUNAI</td>
            <td class="text-right">Rp. {{ number_format($penjualan->bayar, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="font-bold">KEMBALI</td>
            <td class="text-right font-bold">Rp. {{ number_format($penjualan->kembalian, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="text-center" style="margin-top: 10px;">
        <div>*** TERIMA KASIH ***</div>
        <div>Selamat Menikmati Pempek Asli Palembang</div>
    </div>

    <script>
        window.onload = function() {
            // Auto open print dialog if opened in popup
            if (window.opener) {
                window.print();
            }
        };
    </script>
</body>
</html>
