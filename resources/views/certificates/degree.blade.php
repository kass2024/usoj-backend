<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>USJ Degree Certificate</title>
    <style>
        @page { size: A4 portrait; margin: 5mm; }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            background: #FFFFFF;
        }

        .page {
            width: 100%;
            max-width: 200mm;
            margin: 0 auto;
            background: #FFFFFF;
            font-family: "Times New Roman", Times, serif;
            color: #000;
        }

        .frame {
            width: 100%;
            border: 2px solid #000;
            padding: 8mm 6mm 10mm 6mm;
        }

        .header {
            text-align: center;
            font-family: Arial, Helvetica, sans-serif;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header .line1 { font-size: 15pt; margin: 0; }
        .header .line2 { font-size: 13pt; margin: 1mm 0 0 0; }

        .crest {
            display: block;
            margin: 3mm auto 0 auto;
            width: 20mm;
            height: auto;
        }

        .content-wrap {
            width: 100%;
            margin-top: 5mm;
        }

        .main {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .main td { vertical-align: top; word-wrap: break-word; }

        .photo-cell {
            width: 22%;
            text-align: center;
            padding-top: 5mm;
        }

        .photo-cell img {
            width: 24mm;
            height: 30mm;
            object-fit: cover;
            border: 1px solid #666;
        }

        .body-cell { text-align: center; padding: 0 2mm; }
        .body-cell.with-photo { width: 78%; }
        .body-cell.no-photo { width: 100%; padding: 0 4mm; }

        .lead { font-size: 11.5pt; margin: 0 0 3mm 0; }
        .student-name {
            font-size: 18pt;
            font-style: italic;
            font-weight: bold;
            margin: 0 0 4mm 0;
            line-height: 1.15;
        }
        .bridge { font-size: 11pt; margin: 0 0 2mm 0; line-height: 1.3; }
        .degree-name {
            font-size: 12pt;
            font-style: italic;
            font-weight: bold;
            margin: 3mm 0;
            line-height: 1.25;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .class-line { font-size: 11pt; font-style: italic; margin: 2mm 0; }
        .institution { font-size: 11pt; margin: 2mm 0 3mm 0; }
        .date-line { font-size: 11pt; font-weight: bold; margin-top: 2mm; }

        .signatures {
            width: 100%;
            margin-top: 14mm;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 3mm;
        }

        .sign-box { width: 100%; }

        .sign-graphic {
            height: 28mm;
            text-align: center;
            vertical-align: bottom;
        }

        .sign-graphic img.vc {
            width: 38mm;
            max-height: 14mm;
            display: block;
            margin: 0 auto;
        }

        .registrar-stack {
            width: 42mm;
            margin: 0 auto;
            text-align: center;
        }

        .registrar-stack img.stamp {
            width: 28mm;
            height: 28mm;
            display: block;
            margin: 0 auto;
        }

        .registrar-stack img.sig {
            width: 32mm;
            max-height: 10mm;
            display: block;
            margin: -2mm auto 0 auto;
        }

        .sign-line {
            border-top: 1px solid #000;
            width: 42mm;
            margin: 2mm auto 0 auto;
            height: 0;
        }

        .sign-title {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7.5pt;
            font-weight: bold;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            margin-top: 2mm;
            line-height: 1.2;
        }
    </style>
</head>
<body>
<div class="page">
    <div class="frame">
        <div class="header">
            <p class="line1">University of Saint Joseph</p>
            <p class="line2">Mbarara</p>
            @if ($crest_data_uri)
                <img class="crest" src="{{ $crest_data_uri }}" alt="USJ Crest">
            @endif
        </div>

        <div class="content-wrap">
            @if ($show_photo && $photo_data_uri)
                <table class="main">
                    <tr>
                        <td class="photo-cell">
                            <img src="{{ $photo_data_uri }}" alt="Student Photo">
                        </td>
                        <td class="body-cell with-photo">
                            <p class="lead">This is to certify that</p>
                            <p class="student-name">{{ $student_name }}</p>
                            <p class="bridge">has successfully completed a</p>
                            <p class="bridge">Course of study leading to the award of</p>
                            <p class="degree-name">{{ ucwords(strtolower($award)) }}</p>
                            <p class="class-line">Class: {{ $degree_class }}</p>
                            <p class="institution">of University Of Saint Joseph Mbarara</p>
                            <p class="date-line">Date: {{ $issue_date }}</p>
                        </td>
                    </tr>
                </table>
            @else
                <table class="main">
                    <tr>
                        <td class="body-cell no-photo">
                            <p class="lead">This is to certify that</p>
                            <p class="student-name">{{ $student_name }}</p>
                            <p class="bridge">has successfully completed a</p>
                            <p class="bridge">Course of study leading to the award of</p>
                            <p class="degree-name">{{ ucwords(strtolower($award)) }}</p>
                            <p class="class-line">Class: {{ $degree_class }}</p>
                            <p class="institution">of University Of Saint Joseph Mbarara</p>
                            <p class="date-line">Date: {{ $issue_date }}</p>
                        </td>
                    </tr>
                </table>
            @endif
        </div>

        <table class="signatures">
            <tr>
                <td>
                    <table class="sign-box">
                        <tr>
                            <td class="sign-graphic">
                                @if ($vc_signature_data_uri)
                                    <img class="vc" src="{{ $vc_signature_data_uri }}" alt="Vice Chancellor Signature">
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="sign-line"></div>
                                <div class="sign-title">Vice Chancellor</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td>
                    <table class="sign-box">
                        <tr>
                            <td class="sign-graphic">
                                <div class="registrar-stack">
                                    @if ($registrar_stamp_only_data_uri)
                                        <img class="stamp" src="{{ $registrar_stamp_only_data_uri }}" alt="Registrar Stamp">
                                    @endif
                                    @if ($registrar_signature_only_data_uri)
                                        <img class="sig" src="{{ $registrar_signature_only_data_uri }}" alt="Registrar Signature">
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="sign-line"></div>
                                <div class="sign-title">Academic Registrar</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
