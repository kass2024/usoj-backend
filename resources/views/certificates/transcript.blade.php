<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>USJ Academic Transcript</title>
    <style>
        @page { size: A4 portrait; margin: 5mm 7mm; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #000;
        }

        table { border-collapse: collapse; table-layout: fixed; }

        .sheet {
            width: 100%;
            border: 2px solid #E89828;
            padding: 3mm;
            position: relative;
        }

        .watermark {
            position: absolute;
            top: 42%;
            left: 50%;
            width: 70mm;
            opacity: 0.07;
            z-index: 0;
        }

        .content { position: relative; z-index: 1; }

        .header { text-align: center; margin-bottom: 2mm; }

        .header .crest { width: 14mm; height: auto; margin-bottom: 1mm; }

        .header .uni-name { font-size: 11pt; font-weight: bold; margin: 0; }
        .header .uni-city { font-size: 10pt; font-weight: bold; margin: 0; }
        .header .contact { font-size: 7.5pt; line-height: 1.35; margin: 1mm 0 0 0; }
        .header .office { font-size: 8.5pt; font-weight: bold; margin-top: 1mm; }

        .title-bar {
            background: #E89828;
            color: #fff;
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
            padding: 1.5mm 0;
            margin: 2mm 0;
            letter-spacing: 0.5px;
        }

        .student-box { width: 100%; border: 1px solid #008000; margin-bottom: 2mm; }
        .student-box td { border: 1px solid #008000; padding: 2mm; vertical-align: top; }
        .student-box .photo { width: 17%; text-align: center; }
        .student-box .photo img { width: 22mm; height: 28mm; border: 1px solid #888; object-fit: cover; display: block; margin: 0 auto; }
        .student-box .photo-id { font-size: 7.5pt; margin-top: 1mm; font-weight: bold; }
        .student-box .meta { width: 58%; font-size: 8.5pt; line-height: 1.55; }
        .student-box .meta b { display: inline-block; width: 30mm; font-weight: bold; }
        .student-box .qr { width: 25%; text-align: center; font-size: 7.5pt; }
        .student-box .qr img { width: 20mm; height: 20mm; }
        .student-box .qr .serial { margin-top: 1mm; font-weight: bold; font-size: 7.5pt; }

        .results-wrap {
            border: 1px solid #008000;
            padding: 1.5mm;
            margin-bottom: 2mm;
        }

        .semester-grid { width: 100%; }
        .semester-grid td { width: 50%; vertical-align: top; padding: 0 1.5mm 2mm 1.5mm; }

        .semester-title {
            color: #0066B3;
            font-weight: bold;
            font-size: 8pt;
            text-transform: uppercase;
            text-decoration: underline;
            margin: 0 0 1mm 0;
        }

        .course-table { width: 100%; font-size: 7.5pt; margin-bottom: 0.5mm; }
        .course-table th {
            border-bottom: 1px solid #0066B3;
            color: #0066B3;
            font-weight: bold;
            padding: 0.6mm 0.4mm;
            text-align: center;
            font-size: 7.5pt;
        }
        .course-table td {
            border-bottom: 1px solid #D9EAF6;
            padding: 0.5mm 0.4mm;
            text-align: center;
            word-wrap: break-word;
        }
        .course-table tr.alt td { background: #F0F8F0; }
        .course-table .left { text-align: left; }

        .gpa-row {
            color: #008000;
            font-weight: bold;
            font-size: 8pt;
            margin: 1mm 0 1.5mm 0;
        }

        .footer-row {
            width: 100%;
            margin-top: 1mm;
        }

        .footer-row td { vertical-align: top; }

        .summary-cell {
            width: 48%;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.5;
            word-wrap: break-word;
            padding-right: 2mm;
        }

        .sign-cell { width: 52%; vertical-align: top; }

        .sign-inner { width: 100%; }
        .sign-inner td { vertical-align: middle; }

        .sign-lines-cell {
            text-align: right;
            font-size: 8.5pt;
            line-height: 2;
            padding-right: 1mm;
        }

        .sign-lines-cell .dots {
            display: inline-block;
            width: 38mm;
            border-bottom: 1px dotted #000;
            vertical-align: baseline;
            margin-left: 1mm;
        }

        .registrar-label {
            color: #0066B3;
            font-weight: bold;
            font-size: 8.5pt;
            line-height: 1.7;
        }

        .stamp-cell {
            width: 34mm;
            text-align: left;
            vertical-align: middle;
        }

        .stamp-cell img {
            width: 38mm;
            max-height: 28mm;
            height: auto;
            display: block;
            margin-left: -28mm;
        }

        .note-box {
            background: #E89828;
            margin-top: 2mm;
            padding: 2mm 2.5mm;
            font-size: 7pt;
            line-height: 1.4;
            word-wrap: break-word;
        }

        .note-box b { color: #fff; font-size: 7.5pt; }

        .page-break { page-break-before: always; }

        .key-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3mm;
        }
        .section-title {
            font-weight: bold;
            font-size: 9pt;
            margin: 2mm 0 1mm 0;
            text-transform: uppercase;
        }
        .key-table { width: 100%; font-size: 7.5pt; margin-bottom: 2mm; }
        .key-table, .key-table th, .key-table td { border: 1px solid #000; }
        .key-table th, .key-table td { padding: 1mm; vertical-align: top; }
    </style>
</head>
<body>

<div class="sheet">
    @if ($crest_data_uri)
        <img class="watermark" src="{{ $crest_data_uri }}" alt="">
    @endif

    <div class="content">
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
                    <div class="photo-id">{{ str_pad((string) $student->id, 10, '0', STR_PAD_LEFT) }}</div>
                </td>
                <td class="meta">
                    <div><b>REGISTRATION NO:</b> {{ strtoupper($student->reg_number) }}</div>
                    <div><b>NAME:</b> {{ $student_fullname }}</div>
                    <div><b>GENDER:</b> N/A</div>
                    <div><b>DATE OF BIRTH:</b> N/A</div>
                    <div><b>NATIONALITY:</b> UGANDAN</div>
                    <div><b>FACULTY:</b> {{ $faculty }}</div>
                    <div><b>PROGRAM:</b> {{ $program }}</div>
                    <div><b>COMPLETION YEAR:</b> {{ $completion_year }}</div>
                </td>
                <td class="qr">
                    <img src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(90)->generate('USJ Transcript | ' . $student->reg_number . ' | ' . $student_fullname)) }}" alt="QR">
                    <div class="serial">{{ $serial_number }}</div>
                </td>
            </tr>
        </table>

        <div class="results-wrap">
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
        </div>

        <table class="footer-row">
            <tr>
                <td class="summary-cell">
                    FINAL CGPA: {{ number_format($final_cgpa, 2) }}<br>
                    AWARD: {{ $award }}<br>
                    CLASS: {{ $class_label }}
                </td>
                <td class="sign-cell">
                    <table class="sign-inner">
                        <tr>
                            <td class="sign-lines-cell">
                                Signed:<span class="dots"></span><br>
                                <span class="registrar-label">Academic Registrar</span><br>
                                Date &amp; Stamp:<span class="dots"></span> {{ now()->format('d/m/Y') }}
                            </td>
                            <td class="stamp-cell">
                                @if ($registrar_stamp_data_uri)
                                    <img src="{{ $registrar_stamp_data_uri }}" alt="Registrar Stamp and Signature">
                                @endif
                            </td>
                        </tr>
                    </table>
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
</div>

@include('certificates.transcript-grading-key')

</body>
</html>
