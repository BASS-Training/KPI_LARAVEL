<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KPI Team Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; }
        h1, h2 { margin: 0; }
        .header, .section { margin-bottom: 20px; }
        .grid { width: 100%; }
        .grid td { padding: 10px; border: 1px solid #e5e7eb; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #f9fafb; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $company }}</h1>
        <p class="muted">Laporan KPI Tim {{ $role_label ? '• ' . $role_label : '' }}</p>
        <p>Periode {{ $period_label }}</p>
        <p class="muted">Generated at {{ $generated_at->format('d M Y H:i') }}</p>
    </div>

    <table class="grid">
        <tr>
            <td>Total Karyawan<br><strong>{{ $summary['employee_count'] }}</strong></td>
            <td>Rata-rata KPI<br><strong>{{ number_format((float) $summary['average_kpi'], 2) }}</strong></td>
            <td>Top Performer<br><strong>{{ $summary['top_performer']?->user?->nama ?? '-' }}</strong></td>
            <td>Low Performer<br><strong>{{ $summary['low_performer']?->user?->nama ?? '-' }}</strong></td>
        </tr>
    </table>

    <div class="section">
        <h2>Ranking Tim</h2>
        <table style="margin-top: 10px;">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Nama</th>
                    <th>Role</th>
                    <th>Score</th>
                    <th>Status</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ranking as $row)
                    <tr>
                        <td>{{ $row->rank }}</td>
                        <td>{{ $row->user?->nama }}</td>
                        <td>{{ $row->role?->name ?? $row->user?->jabatan }}</td>
                        <td>{{ number_format((float) $row->normalized_score, 2) }}</td>
                        <td>{{ strtoupper($row->status) }}</td>
                        <td>{{ $row->grade }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
