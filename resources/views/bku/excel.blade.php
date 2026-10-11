<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        .num { mso-number-format: "\#\,\#\#0"; }
        .text { mso-number-format: "\@"; }
        .date { mso-number-format: "dd\/mm\/yyyy"; }
        .header-title { font-size: 14pt; font-weight: bold; text-align: center; }
        .header-sub { font-size: 11pt; font-weight: bold; text-align: center; }
        .th-debet { background-color: #d4edda; font-weight: bold; text-align: center; border: 1px solid #000; }
        .th-kredit { background-color: #f8d7da; font-weight: bold; text-align: center; border: 1px solid #000; }
        .border-all { border: 1px solid #000; }
    </style>
</head>
<body>
    <table border="0" cellpadding="2" cellspacing="0" width="100%">
        <tr>
            <td colspan="8" class="header-title">BUKU KAS UMUM (BKU)</td>
        </tr>
        <tr>
            <td colspan="8" class="header-sub">PERIODE BULAN {{ $bkuData['periode_title'] }}</td>
        </tr>
        <tr>
            <td colspan="8" class="header-sub">{{ strtoupper($wilayahLabel) }}</td>
        </tr>
        <tr><td colspan="8"></td></tr>

        <!-- Table Header -->
        <tr>
            <th colspan="4" class="th-debet">PEMASUKAN</th>
            <th colspan="4" class="th-kredit">PENGELUARAN</th>
        </tr>
        <tr>
            <th class="border-all" style="background-color: #f2f2f2; width: 100px;">TANGGAL</th>
            <th class="border-all" style="background-color: #f2f2f2; width: 90px;">KODE</th>
            <th class="border-all" style="background-color: #f2f2f2; width: 300px;">URAIAN</th>
            <th class="border-all" style="background-color: #f2f2f2; width: 130px;">JUMLAH (Rp)</th>
            <th class="border-all" style="background-color: #f2f2f2; width: 100px;">TANGGAL</th>
            <th class="border-all" style="background-color: #f2f2f2; width: 90px;">KODE</th>
            <th class="border-all" style="background-color: #f2f2f2; width: 300px;">URAIAN</th>
            <th class="border-all" style="background-color: #f2f2f2; width: 130px;">JUMLAH (Rp)</th>
        </tr>

        <!-- Table Body -->
        @foreach($bkuData['t_account_rows'] as $row)
            @php
                $in = $row['debet'];
                $out = $row['kredit'];
            @endphp
            <tr>
                <!-- Sisi Kiri -->
                @if($in)
                    <td class="border-all date" align="center">{{ $in['tanggal'] }}</td>
                    <td class="border-all text" align="center">{{ $in['kode'] }}</td>
                    <td class="border-all text">{{ $in['uraian'] }}</td>
                    <td class="border-all num" align="right">{{ $in['jumlah'] }}</td>
                @else
                    <td class="border-all"></td>
                    <td class="border-all"></td>
                    <td class="border-all"></td>
                    <td class="border-all"></td>
                @endif

                <!-- Sisi Kanan -->
                @if($out)
                    <td class="border-all date" align="center">{{ $out['tanggal'] }}</td>
                    <td class="border-all text" align="center">{{ $out['kode'] }}</td>
                    <td class="border-all text">{{ $out['uraian'] }}</td>
                    <td class="border-all num" align="right">{{ $out['jumlah'] }}</td>
                @else
                    <td class="border-all"></td>
                    <td class="border-all"></td>
                    <td class="border-all"></td>
                    <td class="border-all"></td>
                @endif
            </tr>
        @endforeach

        <!-- Table Footer -->
        <tr style="background-color: #f8f9fa; font-weight: bold;">
            <td colspan="3" class="border-all" align="right">Total Pemasukan Bulan Ini:</td>
            <td class="border-all num" align="right">{{ $bkuData['total_pemasukan_bulan_ini'] }}</td>
            <td colspan="3" class="border-all" align="right">Total Pengeluaran Bulan Ini:</td>
            <td class="border-all num" align="right">{{ $bkuData['total_pengeluaran'] }}</td>
        </tr>
        <tr style="background-color: #f8f9fa; font-weight: bold;">
            <td colspan="3" class="border-all" align="right">JUMLAH PEMASUKAN:</td>
            <td class="border-all num" align="right">{{ $bkuData['jumlah_pemasukan'] }}</td>
            <td colspan="3" class="border-all" align="right">SALDO BUKU KAS AKHIR:</td>
            <td class="border-all num" align="right">{{ $bkuData['saldo_buku'] }}</td>
        </tr>
        <tr style="background-color: #e9ecef; font-weight: bold;">
            <td colspan="3" class="border-all" align="right">TOTAL SEIMBANG:</td>
            <td class="border-all num" align="right">{{ $bkuData['jumlah_pemasukan'] }}</td>
            <td colspan="3" class="border-all" align="right">TOTAL SEIMBANG:</td>
            <td class="border-all num" align="right">{{ $bkuData['total_seimbang_kanan'] }}</td>
        </tr>

        <!-- Signatures Area -->
        <tr><td colspan="8"></td></tr>
        <tr><td colspan="8"></td></tr>
        <tr>
            <td colspan="3" align="center">Mengetahui,</td>
            <td colspan="2"></td>
            <td colspan="3" align="center">Lamongan, {{ $tanggalCetak }}</td>
        </tr>
        <tr>
            <td colspan="3" align="center"><strong>Ketua JPZIS NU</strong></td>
            <td colspan="2"></td>
            <td colspan="3" align="center"><strong>Bendahara</strong></td>
        </tr>
        <tr><td colspan="8" style="height: 50px;"></td></tr>
        <tr>
            <td colspan="3" align="center"><strong>( {{ $namaKetua }} )</strong></td>
            <td colspan="2"></td>
            <td colspan="3" align="center"><strong>( {{ $namaBendahara }} )</strong></td>
        </tr>
    </table>
</body>
</html>
