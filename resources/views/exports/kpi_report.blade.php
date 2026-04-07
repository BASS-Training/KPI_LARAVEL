<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan KPI - {{ $user->nama }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; background: #fff; }

        .header { background: #1e3a5f; color: #fff; padding: 24px 32px; margin-bottom: 24px; }
        .header-title { font-size: 20px; font-weight: bold; margin-bottom: 4px; }
        .header-sub { font-size: 11px; opacity: 0.75; }

        .section { margin: 0 32px 20px; }
        .section-title { font-size: 13px; font-weight: bold; color: #1e3a5f; border-bottom: 2px solid #1e3a5f; padding-bottom: 6px; margin-bottom: 12px; }

        .info-grid { display: table; width: 100%; border-collapse: collapse; }
        .info-row { display: table-row; }
        .info-cell { display: table-cell; padding: 5px 8px; border: 1px solid #e2e8f0; width: 50%; }
        .info-label { font-weight: bold; color: #475569; font-size: 10px; }
        .info-value { color: #1e293b; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th { background: #1e3a5f; color: #fff; padding: 8px 10px; text-align: left; font-size: 10px; font-weight: bold; }
        td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        tr:nth-child(even) td { background: #f8fafc; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: bold; }
        .badge-excellent { background: #dbeafe; color: #1d4ed8; }
        .badge-good { background: #dcfce7; color: #15803d; }
        .badge-average { background: #fef9c3; color: #a16207; }
        .badge-bad { background: #fee2e2; color: #dc2626; }
        .badge-bs { background: #dcfce7; color: #15803d; }
        .badge-b { background: #dbeafe; color: #1d4ed8; }
        .badge-c { background: #fef9c3; color: #a16207; }
        .badge-k { background: #fee2e2; color: #dc2626; }

        .score-box { text-align: center; padding: 16px; border: 2px solid #1e3a5f; border-radius: 8px; margin-bottom: 20px; }
        .score-number { font-size: 36px; font-weight: bold; color: #1e3a5f; }
        .score-label { font-size: 14px; color: #475569; margin-top: 4px; }

        .footer { margin-top: 32px; padding: 12px 32px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>

<div class="header">
    <div class="header-title">BASS Training Center &amp; Consultant</div>
    <div class="header-sub">Laporan KPI Karyawan &mdash; Periode {{ $bulan }} {{ $tahun }}</div>
    <div class="header-sub" style="margin-top:4px">Digenerate: {{ $generatedAt }}</div>
</div>

{{-- Employee Info --}}
<div class="section">
    <div class="section-title">Informasi Karyawan</div>
    <table>
        <tr>
            <td style="width:50%"><span class="info-label">NIP</span><br>{{ $user->nip }}</td>
            <td style="width:50%"><span class="info-label">Nama</span><br>{{ $user->nama }}</td>
        </tr>
        <tr>
            <td><span class="info-label">Jabatan</span><br>{{ $user->jabatan }}</td>
            <td><span class="info-label">Departemen / Divisi</span><br>{{ $user->departemen }}{{ $user->division ? ' / '.$user->division->nama : '' }}</td>
        </tr>
    </table>
</div>

{{-- KPI Score Summary --}}
<div class="section">
    <div class="section-title">Ringkasan Skor KPI</div>
    <table>
        <tr>
            <td style="width:50%;text-align:center">
                <div style="font-size:28px;font-weight:bold;color:#1e3a5f">{{ $kpiData['total'] }}</div>
                <div style="font-size:11px;color:#64748b">Total Skor (0-5)</div>
            </td>
            <td style="width:50%;text-align:center">
                <div style="font-size:20px;font-weight:bold;color:#1e3a5f">{{ $kpiData['predikat'] }}</div>
                <div style="font-size:11px;color:#64748b">Predikat</div>
            </td>
        </tr>
    </table>
</div>

{{-- KPI Components --}}
@if(!empty($kpiData['components']))
<div class="section">
    <div class="section-title">Komponen KPI</div>
    <table>
        <thead>
            <tr>
                <th>Komponen</th>
                <th>Bobot</th>
                <th>Target</th>
                <th>Tipe</th>
                <th>Skor</th>
                <th>Nilai Bobot</th>
            </tr>
        </thead>
        <tbody>
            @foreach($kpiData['components'] as $comp)
            <tr>
                <td>{{ $comp['objectives'] }}</td>
                <td>{{ $comp['bobot'] * 100 }}%</td>
                <td>{{ $comp['target'] ?? '-' }}</td>
                <td>{{ $comp['tipe'] }}</td>
                <td>{{ $comp['skor'] }}</td>
                <td>{{ $comp['nilai_bobot'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- KPI Progress Reports --}}
@if($reports->isNotEmpty())
<div class="section">
    <div class="section-title">Laporan Progress KPI</div>
    <table>
        <thead>
            <tr>
                <th>Komponen</th>
                <th>Tanggal</th>
                <th>Target</th>
                <th>Aktual</th>
                <th>%</th>
                <th>Predikat</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reports as $r)
            <tr>
                <td>{{ $r->kpiComponent?->objectives ?? '-' }}</td>
                <td>{{ $r->tanggal?->format('d M Y') ?? '-' }}</td>
                <td>{{ $r->nilai_target ?? '-' }}</td>
                <td>{{ $r->nilai_aktual ?? '-' }}</td>
                <td>{{ $r->persentase ?? '-' }}%</td>
                <td>
                    @if($r->score_label === 'excellent')
                        <span class="badge badge-excellent">Excellent</span>
                    @elseif($r->score_label === 'good')
                        <span class="badge badge-good">Good</span>
                    @elseif($r->score_label === 'average')
                        <span class="badge badge-average">Average</span>
                    @elseif($r->score_label === 'bad')
                        <span class="badge badge-bad">Bad</span>
                    @else
                        -
                    @endif
                </td>
                <td>{{ ucfirst($r->status) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="footer">
    Dokumen ini digenerate secara otomatis oleh Sistem KPI BASS Training Center &amp; Consultant.
    Tidak memerlukan tanda tangan basah. &copy; {{ now()->year }} BASS Training.
</div>

</body>
</html>
