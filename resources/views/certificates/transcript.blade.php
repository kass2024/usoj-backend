<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>USJ Academic Transcript</title>
    <style>
        @page { size: A4 portrait; margin: 6mm 8mm; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Times New Roman", Times, serif;
            font-size: 8.5px;
            color: #000;
        }

        table { border-collapse: collapse; table-layout: fixed; }

        .sheet {
            width: 100%;
            max-width: 100%;
            border: 3px double #E89828;
            padding: 3mm;
            overflow: hidden;
        }

        .header { text-align: center; margin-bottom: 2mm; }

        .header .crest { width: 16mm; height: auto; margin-bottom: 1mm; }

        .header .uni-name { font-size: 12px; font-weight: bold; margin: 0; }
        .header .uni-city { font-size: 11px; font-weight: bold; margin: 0; }
        .header .contact { font-size: 7.5px; line-height: 1.35; margin: 1mm 0 0 0; }
        .header .office { font-size: 8.5px; font-weight: bold; margin-top: 1mm; }

        .title-bar {
            background: #E89828;
            color: #fff;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            padding: 1.8mm 0;
            margin: 2mm 0;
        }

        .student-box { width: 100%; border: 1px solid #000; margin-bottom: 2mm; table-layout: fixed; }
        .student-box td { border: 1px solid #000; padding: 2mm; vertical-align: top; }
        .student-box .photo { width: 18%; text-align: center; }
        .student-box .photo img { width: 24mm; height: 30mm; border: 1px solid #888; object-fit: cover; }
        .student-box .meta { width: 57%; font-size: 8px; line-height: 1.45; }
        .student-box .meta b { display: inline-block; width: 28mm; }
        .student-box .qr { width: 25%; text-align: center; font-size: 7px; }
        .student-box .qr img { width: 18mm; height: 18mm; }

        .semester-grid { width: 100%; table-layout: fixed; margin-bottom: 1mm; }
        .semester-grid td { width: 50%; vertical-align: top; padding: 0 1mm 2mm 1mm; }

        .semester-title {
            color: #2980B9;
            font-weight: bold;
            font-size: 7.5px;
            text-transform: uppercase;
            margin: 0 0 1mm 0;
        }

        .course-table { width: 100%; table-layout: fixed; font-size: 7px; margin-bottom: 0.5mm; }
        .course-table th {
            border-bottom: 1px solid #2980B9;
            color: #2980B9;
            font-weight: bold;
            padding: 0.8mm 0.5mm;
            text-align: center;
        }
        .course-table td {
            border-bottom: 1px solid #D9EAF6;
            padding: 0.7mm 0.5mm;
            text-align: center;
            word-wrap: break-word;
        }
        .course-table tr.alt td { background: #F2F7FB; }
        .course-table .left { text-align: left; }

        .gpa-row {
            color: #2980B9;
            font-weight: bold;
            font-size: 7.5px;
            margin-bottom: 1.5mm;
        }

        .footer-row {
            width: 100%;
            margin-top: 2mm;
            table-layout: fixed;
        }

        .footer-row td { vertical-align: top; }

        .summary-cell {
            width: 52%;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.45;
            word-wrap: break-word;
            padding-right: 2mm;
        }

        .sign-cell { width: 48%; }

        .sign-area {
            position: relative;
            width: 100%;
            min-height: 20mm;
            text-align: right;
        }

        .sign-lines {
            font-size: 8px;
            line-height: 2.6;
            text-align: right;
            padding-right: 26mm;
        }

        .sign-lines .leader {
            display: inline-block;
            width: 32mm;
            border-bottom: 1px dotted #000;
            vertical-align: baseline;
            margin-left: 1mm;
        }

        .stamp-overlay {
            position: absolute;
            right: 0;
            top: -1mm;
            width: 34mm;
            max-height: 22mm;
            height: auto;
        }

        .note-box {
            background: #E89828;
            margin-top: 2mm;
            padding: 2mm;
            font-size: 6.5px;
            line-height: 1.35;
            word-wrap: break-word;
        }
        .note-box b { color: #fff; }

        .page-break { page-break-before: always; }

        .key-title {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3mm;
        }
        .section-title {
            font-weight: bold;
            font-size: 8.5px;
            margin: 2mm 0 1mm 0;
            text-transform: uppercase;
        }
        .key-table { width: 100%; font-size: 7px; margin-bottom: 2mm; }
        .key-table, .key-table th, .key-table td { border: 1px solid #000; }
        .key-table th, .key-table td { padding: 1mm; vertical-align: top; }
    </style>
</head>
<body>

<div class="sheet">
    <div class="header">
        @if ($crest_data_uri)
            <img class="crest" src="{{ $crest_data_uri }}" alt="USJ Crest">
        @endif
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
            <td class="photo">
                @if ($photo_data_uri)
                    <img src="{{ $photo_data_uri }}" alt="Student Photo">
                @endif
            </td>
            <td class="meta">
                <div><b>REGISTRATION NO:</b> {{ strtoupper($student->reg_number) }}</div>
                <div><b>NAME:</b> {{ $student_fullname }}</div>
                <div><b>EMAIL:</b> {{ strtoupper($student->email) }}</div>
                <div><b>PHONE:</b> {{ strtoupper($student->phone ?: 'N/A') }}</div>
                <div><b>FACULTY:</b> {{ $faculty }}</div>
                <div><b>PROGRAM:</b> {{ $program }}</div>
                <div><b>COMPLETION YEAR:</b> {{ $completion_year }}</div>
            </td>
            <td class="qr">
                <img src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(90)->generate('USJ Transcript | ' . $student->reg_number . ' | ' . $student_fullname)) }}" alt="QR">
                <div style="margin-top:1mm;">{{ $serial_number }}</div>
            </td>
        </tr>
    </table>

    <table class="semester-grid">
        @foreach (array_chunk($semesters, 2) as $row)
            <tr>
                @foreach ($row as $colIndex => $semester)
                    <td>
                        <p class="semester-title">{{ $semester['title'] }}</p>
                        <table class="course-table">
                            <thead>
                                <tr>
                                    <th style="width:16%;">CODE</th>
                                    <th style="width:44%;">COURSE/SUBJECT TITLE</th>
                                    <th style="width:10%;">CU</th>
                                    <th style="width:15%;">GP</th>
                                    <th style="width:15%;">GD</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($semester['courses'] as $courseIndex => $course)
                                    <tr class="{{ $courseIndex % 2 ? 'alt' : '' }}">
                                        <td>{{ $course['code'] }}</td>
                                        <td class="left">{{ strtoupper($course['name']) }}</td>
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
                                &nbsp;&nbsp;&nbsp; CGPA {{ number_format($semester['cgpa'], 2) }}
                            @endif
                        </div>
                    </td>
                @endforeach
                @if (count($row) === 1)
                    <td></td>
                @endif
            </tr>
        @endforeach
    </table>

    <table class="footer-row">
        <tr>
            <td class="summary-cell">
                FINAL CGPA: {{ number_format($final_cgpa, 2) }}<br>
                AWARD: {{ $award }}<br>
                CLASS: {{ $class_label }}
            </td>
            <td class="sign-cell">
                <div class="sign-area">
                    <div class="sign-lines">
                        Signed:<span class="leader"></span><br>
                        Date &amp; Stamp:<span class="leader"></span> {{ now()->format('d/m/Y') }}
                    </div>
                    @if ($registrar_stamp_data_uri)
                        <img class="stamp-overlay" src="{{ $registrar_stamp_data_uri }}" alt="Registrar Stamp and Signature">
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="note-box">
        <b>NOTE:</b>
        1. The transcript is not valid without the official stamp of the University of Saint Joseph Mbarara.
        2. The Medium of Instruction is English (UK).
        3. This transcript is verifiable online at https://e-learning.uosj.ac.ug
    </div>
</div>

@include('certificates.transcript-grading-key')

</body>
</html>
