@extends('layouts.account')

@section('title')
    Dashboard - {{ config('app.name') }}
@stop

@section('content')
    <div class="main-content">
        <section class="section">
            <div class="row">
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                    <div class="card card-statistic-2">
                        <div class="card-icon shadow-primary bg-primary">
                            <i class="fas fa-money-check-alt"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>SEMUA SALDO </h4>
                            </div>
                            <div class="card-body" style="font-size: 20px">
                                {{ rupiah($saldo_selama_ini) }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                    <div class="card card-statistic-2">
                        <div class="card-icon shadow-primary bg-primary">
                            <i class="fas fa-money-check-alt"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>SALDO BULAN INI</h4>
                            </div>
                            <div class="card-body" style="font-size: 20px">
                                {{ rupiah($saldo_bulan_ini) }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                    <div class="card card-statistic-2">
                        <div class="card-icon shadow-primary bg-primary">
                            <i class="fas fa-money-check-alt"></i>
                        </div>
                        <div class="card-wrap">
                            <div class="card-header">
                                <h4>SALDO BULAN LALU</h4>
                            </div>
                            <div class="card-body" style="font-size: 20px">
                                {{ rupiah($saldo_bulan_lalu) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4><i class="fas fa-chart-pie"></i> STATISTIK KEUANGAN DALAM 1 TAHUN</h4>
                            <div class="card-header-action">
                                <form action="{{ route('account.dashboard.index') }}" method="GET" id="yearForm">
                                    <select name="year" class="form-control" onchange="document.getElementById('yearForm').submit()" style="font-weight: bold; border-radius: 20px; cursor: pointer;">
                                        @foreach($year_list as $year)
                                            <option value="{{ $year }}" {{ $selected_year == $year ? 'selected' : '' }}>
                                                Tahun {{ $year }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                        </div>

                        <div class="card-body">
                            <div id="container" style="min-height: 380px;"></div>
                        </div>
                    </div>
                </div>
            </div>

        </section>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Highcharts.chart('container', {
                chart: {
                    type: 'column'
                },
                title: {
                    text: 'Statistik Pemasukan & Pengeluaran Tahun {{ $selected_year }}'
                },
                xAxis: {
                    categories: [
                        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                    ],
                    crosshair: true
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Nominal (Rp)'
                    },
                    labels: {
                        formatter: function () {
                            return 'Rp. ' + Highcharts.numberFormat(this.value, 0, ',', '.');
                        }
                    }
                },
                tooltip: {
                    headerFormat: '<span style="font-size:12px; font-weight:bold;">{point.key}</span><table>',
                    pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                        '<td style="padding:0; font-weight:bold;"><b>Rp. {point.y:,.0f}</b></td></tr>',
                    footerFormat: '</table>',
                    shared: true,
                    useHTML: true
                },
                plotOptions: {
                    column: {
                        pointPadding: 0.2,
                        borderWidth: 0,
                        borderRadius: 4
                    }
                },
                series: [{
                    name: 'Uang Masuk (Debit)',
                    color: '#28a745',
                    data: {!! json_encode($chart_debit) !!}
                }, {
                    name: 'Uang Keluar (Credit)',
                    color: '#dc3545',
                    data: {!! json_encode($chart_credit) !!}
                }],
                credits: {
                    enabled: false
                }
            });
        });
    </script>
@stop
