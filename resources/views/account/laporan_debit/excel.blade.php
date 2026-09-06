<table>
    <thead>
        <tr>
            <th colspan="7" style="text-align: center; font-weight: bold; font-size: 16px;">LAPORAN UANG MASUK - {{ config('app.name') }}</th>
        </tr>
        <tr>
            <th colspan="7" style="text-align: center; font-size: 12px;">Periode: {{ $tanggal_awal }} s/d {{ $tanggal_akhir }}</th>
        </tr>
        <tr></tr>
        <tr style="background-color: #f2f2f2;">
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">NO.</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">TANGGAL</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">KATEGORI</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">BARANG TERJUAL</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">QTY</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">NOMINAL (Rp)</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">KETERANGAN</th>
        </tr>
    </thead>
    <tbody>
        @php $no = 1; @endphp
        @foreach($debit as $item)
            <tr>
                <td style="text-align: center; border: 1px solid #000000;">{{ $no++ }}</td>
                <td style="text-align: center; border: 1px solid #000000;">{{ $item->debit_date }}</td>
                <td style="border: 1px solid #000000;">{{ $item->category_name }}</td>
                <td style="border: 1px solid #000000;">{{ $item->nama_barang ?: '-' }}</td>
                <td style="text-align: center; border: 1px solid #000000;">{{ $item->qty ?: '-' }}</td>
                <td style="text-align: right; border: 1px solid #000000;">{{ $item->nominal }}</td>
                <td style="border: 1px solid #000000;">{{ $item->description }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th colspan="5" style="text-align: right; font-weight: bold; border: 1px solid #000000;">TOTAL</th>
            <th style="text-align: right; font-weight: bold; border: 1px solid #000000;">{{ $total_nominal }}</th>
            <th style="border: 1px solid #000000;"></th>
        </tr>
    </tfoot>
</table>
