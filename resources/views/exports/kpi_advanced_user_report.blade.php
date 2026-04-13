<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KPI User Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; }
        h1, h2, h3 { margin: 0; }
        .header, .summary, .section { margin-bottom: 20px; }
        .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #f9fafb; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $company }}</h1>
        <p class="muted">Laporan KPI Karyawan</p>
        <p>{{ $score->user?->nama }} • {{ $score->user?->jabatan }} • {{ optional($score->period_start)->format('F Y') }}</p>
        <p class="muted">Generated at {{ $generatedAt->format('d M Y H:i') }}</p>
    </div>

    <div class="summary card">
        <h2>Ringkasan</h2>
        <table style="margin-top: 10px;">
            <tr>
                <th>Total Score</th>
                <th>Status</th>
                <th>Grade</th>
            </tr>
            <tr>
                <td>{{ number_format((float) $score->normalized_score, 2) }}</td>
                <td>{{ strtoupper($score->status) }}</td>
                <td>{{ $score->grade }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Breakdown KPI</h2>
        <table style="margin-top: 10px;">
            <thead>
                <tr>
                    <th>Indicator</th>
                    <th>Weight</th>
                    <th>Target</th>
                    <th>Actual</th>
                    <th>Achievement</th>
                    <th>Score</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($score->breakdown ?? []) as $indicator)
                    <tr>
                        <td>{{ $indicator['name'] }}</td>
                        <td>{{ $indicator['weight'] }}</td>
                        <td>{{ $indicator['target_value'] }}</td>
                        <td>{{ $indicator['actual_value'] }}</td>
                        <td>{{ $indicator['achievement_ratio'] }}%</td>
                        <td>{{ $indicator['score'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
