<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>USJ Academic Transcript</title>
    <style>
        @page { size: A4 portrait; margin: 0; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Times New Roman", Times, serif;
            font-size: 9px;
            color: #000;
        }

        .page {
            width: 210mm;
            height: 297mm;
            page-break-after: always;
            position: relative;
            padding: 8mm;
            overflow: hidden;
        }

        .page:last-child { page-break-after: auto; }

        .frame {
            border: 3px double #E89828;
            height: 100%;
            padding: 5mm;
            position: relative;
        }

        .watermark {
            position: absolute;
            top: 38%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.06;
            width: 120mm;
            z-index: 0;
        }

        .content { position: relative; z-index: 1; }

        .header {
            text-align: center;
            margin-bottom: 2mm;
        }

        .header img {
            width: 18mm;
            height: auto;
            margin-bottom: 1mm;
        }

        .header .uni-name {
            font-size: 13px;
            font-weight: bold;
            margin: 0;
            letter-spacing: 0.3px;
        }

        .header .uni-city {
            font-size: 12px;
            font-weight: bold;
            margin: 0;
        }

        .header .contact {
            font-size: 8px;
            line-height: 1.35;
            margin: 1mm 0 0 0;
        }

        .header .office {
            font-size: 9px;
            font-weight: bold;
            margin-top: 1mm;
        }

        .title-bar {
            background: #E89828;
            color: #fff;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            padding: 2mm 0;
            letter-spacing: 0.5px;
            margin: 2mm 0 3mm 0;
        }

        .student-box {
            width: 100%;
            border: 1px solid #000;
            border-collapse: collapse;
            margin-bottom: 3mm;
        }

        .student-box td {
            vertical-align: top;
            padding: 2mm;
            border: 1px solid #000;
        }

        .photo-cell { width: 22%; text-align: center; }

        .photo-cell img {
            width: 28mm;
            height: 34mm;
            object-fit: cover;
            border: 1px solid #999;
        }

        .meta-cell { width: 53%; font-size: 8.5px; }

        .meta-row { margin-bottom: 1.2mm; }

        .meta-row .label {
            font-weight: bold;
            display: inline-block;
            width: 34mm;
        }

        .meta-row .value {
            text-transform: uppercase;
        }

        .qr-cell {
            width: 25%;
            text-align: center;
            font-size: 8px;
        }

        .qr-cell img { width: 22mm; height: 22mm; }

        .semesters-wrap { width: 100%; }

        .semester-col {
            width: 49%;
            display: inline-block;
            vertical-align: top;
            margin-bottom: 2mm;
        }

        .semester-col.left { margin-right: 1%; }
        .semester-col.right { margin-left: 1%; }

        .semester-title {
            color: #2980B9;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
            margin: 0 0 1mm 0;
        }

        .course-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1mm;
            font-size: 7.5px;
        }

        .course-table th {
            border-bottom: 1px solid #2980B9;
            color: #2980B9;
            font-weight: bold;
            padding: 1mm;
            text-align: center;
        }

        .course-table td {
            border-bottom: 1px solid #D9EAF6;
            padding: 0.8mm 1mm;
            text-align: center;
        }

        .course-table tr:nth-child(even) td { background: #F2F7FB; }

        .course-table .subject { text-align: left; }

        .gpa-row {
            color: #2980B9;
            font-weight: bold;
            font-size: 8px;
            margin-bottom: 2mm;
        }

        .gpa-row .cgpa { float: right; }

        .summary {
            margin-top: 2mm;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.5;
        }

        .auth-block {
            margin-top: 3mm;
            width: 100%;
        }

        .auth-block td { vertical-align: bottom; }

        .stamp {
            width: 28mm;
            height: 28mm;
            border: 2px solid #2980B9;
            border-radius: 50%;
            color: #2980B9;
            font-size: 6px;
            text-align: center;
            padding: 4mm 2mm;
            line-height: 1.3;
            font-weight: bold;
        }

        .sign-block {
            text-align: right;
            font-size: 8px;
            line-height: 1.4;
        }

        .note-box {
            background: #E89828;
            color: #000;
            margin-top: 3mm;
            padding: 2mm 3mm;
            font-size: 7.5px;
            line-height: 1.45;
        }

        .note-box .note-label {
            color: #fff;
            font-weight: bold;
            margin-right: 2mm;
        }

        /* Page 2 grading key */
        .key-title {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 4mm;
        }

        .section-title {
            font-weight: bold;
            font-size: 9px;
            margin: 3mm 0 1.5mm 0;
            text-transform: uppercase;
        }

        .key-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5px;
            margin-bottom: 2mm;
        }

        .key-table, .key-table th, .key-table td {
            border: 1px solid #000;
        }

        .key-table th, .key-table td {
            padding: 1mm;
            vertical-align: top;
        }
    </style>
</head>
<body>

<div class="page">
    <div class="frame">
        <img class="watermark" src="{{ public_path('images/usj-crest.png') }}" alt="">

        <div class="content">
            <div class="header">
                <img src="{{ public_path('images/usj-crest.png') }}" alt="USJ Crest">
                <p class="uni-name">University of Saint Joseph</p>
                <p class="uni-city">Mbarara</p>
                <p class="contact">
                    P.O. Box 219, Mbarara Uganda<br>
                    Tel: +256 772 065667 / +256 705 706681<br>
                    Email: uosj@uosj.ac.ug, www.uosj.ac.ug
                </p>
                <p class="office">Office of the Academic Registrar</p>
            </div>

            <div class="title-bar">ACADEMIC TRANSCRIPT</div>

            <table class="student-box">
                <tr>
                    <td class="photo-cell">
                        <img src="{{ $photo_path }}" alt="Student Photo">
                    </td>
                    <td class="meta-cell">
                        <div class="meta-row"><span class="label">REGISTRATION NO:</span><span class="value">{{ $student->reg_number }}</span></div>
                        <div class="meta-row"><span class="label">NAME:</span><span class="value">{{ $student_fullname }}</span></div>
                        <div class="meta-row"><span class="label">EMAIL:</span><span class="value">{{ $student->email }}</span></div>
                        <div class="meta-row"><span class="label">PHONE:</span><span class="value">{{ $student->phone ?: 'N/A' }}</span></div>
                        <div class="meta-row"><span class="label">FACULTY:</span><span class="value">{{ $faculty }}</span></div>
                        <div class="meta-row"><span class="label">PROGRAM:</span><span class="value">{{ $program }}</span></div>
                        <div class="meta-row"><span class="label">COMPLETION YEAR:</span><span class="value">{{ $completion_year }}</span></div>
                    </td>
                    <td class="qr-cell">
                        <img src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(120)->generate('USJ Transcript | ' . $student->reg_number . ' | ' . $student_fullname)) }}" alt="QR">
                        <div style="margin-top:1mm;">{{ $serial_number }}</div>
                    </td>
                </tr>
            </table>

            <div class="semesters-wrap">
                @foreach (array_chunk($semesters, 2) as $row)
                    <div style="width:100%; overflow:hidden;">
                        @foreach ($row as $colIndex => $semester)
                            <div class="semester-col {{ $colIndex === 0 ? 'left' : 'right' }}">
                                <p class="semester-title">{{ $semester['title'] }}</p>
                                <table class="course-table">
                                    <thead>
                                        <tr>
                                            <th style="width:14%;">CODE</th>
                                            <th style="width:46%;">COURSE/SUBJECT TITLE</th>
                                            <th style="width:10%;">CU</th>
                                            <th style="width:15%;">GP</th>
                                            <th style="width:15%;">GD</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($semester['courses'] as $course)
                                            <tr>
                                                <td>{{ $course['code'] }}</td>
                                                <td class="subject">{{ $course['name'] }}</td>
                                                <td>{{ $course['credits'] }}</td>
                                                <td>{{ number_format($course['gp'], 1) }}</td>
                                                <td>{{ $course['gd'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="gpa-row">
                                    GPA. {{ number_format($semester['gpa'], 2) }}
                                    @if ($colIndex === 1 || ($loop->parent->last && $loop->last))
                                        <span class="cgpa">CGPA {{ number_format($semester['cgpa'], 2) }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            <div class="summary">
                FINAL CGPA: {{ number_format($final_cgpa, 2) }}<br>
                AWARD: {{ $award }}<br>
                CLASS: {{ $class_label }}
            </div>

            <table class="auth-block">
                <tr>
                    <td style="width:35%;">
                        <div class="stamp">
                            UNIVERSITY OF SAINT JOSEPH<br>
                            ACADEMIC REGISTRAR<br>
                            Foster Excellence and Integrity
                        </div>
                    </td>
                    <td style="width:65%;">
                        <div class="sign-block">
                            Signed: ________________________<br>
                            Academic Registrar<br>
                            Date &amp; Stamp: {{ now()->format('d/m/Y') }}
                        </div>
                    </td>
                </tr>
            </table>

            <div class="note-box">
                <span class="note-label">NOTE:</span>
                1. The transcript is not valid without the official stamp of the University of Saint Joseph Mbarara.
                2. The Medium of Instruction is English (UK).
                3. This transcript is verifiable online at https://e-learning.uosj.ac.ug
            </div>
        </div>
    </div>
</div>

@include('certificates.transcript-grading-key')

</body>
</html>
