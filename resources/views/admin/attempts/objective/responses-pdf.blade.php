<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Candidate Responses</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
            line-height: 1.4;
        }

        h1 {
            margin: 0;
            font-size: 20px;
        }

        .brand-header {
            width: 100%;
            border-bottom: 2px solid #111827;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        .brand-header td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .brand-logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
        }

        .brand-title {
            text-align: center;
        }

        .brand-title .school-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .brand-title .report-name {
            font-size: 13px;
            color: #4b5563;
        }

        .candidate-photo {
            width: 70px;
            height: 70px;
            border: 1px solid #d1d5db;
            object-fit: cover;
        }

        .meta {
            margin-bottom: 12px;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #f9fafb;
        }

        .meta p {
            margin: 2px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 8px;
            vertical-align: top;
            text-align: left;
        }

        th {
            background: #f3f4f6;
            font-weight: 700;
        }

        .muted {
            color: #6b7280;
        }

        .subject-header {
            margin-top: 16px;
            margin-bottom: 6px;
            padding: 7px 10px;
            border: 1px solid #d1d5db;
            background: #eef2ff;
            font-weight: 700;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <table class="brand-header">
        <tr>
            <td style="width: 20%;">
                @if (! empty($brandLogoDataUri))
                    <img src="{{ $brandLogoDataUri }}" alt="Logo" class="brand-logo">
                @endif
            </td>
            <td style="width: 60%;" class="brand-title">
                <div class="school-name">{{ $siteName }}</div>
                <div class="report-name">Candidate Response Sheet</div>
            </td>
            <td style="width: 20%; text-align: right;">
                @if (! empty($candidatePhotoDataUri))
                    <img src="{{ $candidatePhotoDataUri }}" alt="Candidate" class="candidate-photo">
                @endif
            </td>
        </tr>
    </table>

    <div class="meta">
        <p><strong>Exam:</strong> {{ $exam->title }}</p>
        <p><strong>Candidate Name:</strong> {{ $candidate?->name ?? 'N/A' }}</p>
        <p><strong>Registration Number:</strong> {{ $candidate?->registration_number ?? 'N/A' }}</p>
        <p><strong>Attempt ID:</strong> {{ $attempt->id }}</p>
    </div>

    @php
        $groupedRows = collect($rows)->groupBy(function (array $row) {
            return $row['subject'] !== '' ? $row['subject'] : 'General';
        });
    @endphp

    @forelse ($groupedRows as $subjectName => $subjectRows)
        <div class="subject-header">Subject: {{ $subjectName }}</div>

        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">#</th>
                    <th style="width: 58%;">Question</th>
                    <th style="width: 34%;">Selected Answer</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($subjectRows->values() as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $row['question'] !== '' ? $row['question'] : '—' }}</td>
                        <td>{{ $row['selected_answer'] !== '' ? $row['selected_answer'] : 'No answer selected' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <table>
            <tbody>
                <tr>
                    <td class="muted">No responses found for this attempt.</td>
                </tr>
            </tbody>
        </table>
    @endforelse
</body>
</html>
