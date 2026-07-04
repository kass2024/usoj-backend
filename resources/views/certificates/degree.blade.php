<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>USJ Degree Certificate</title>
    <style>
        @page { size: A4 portrait; margin: 0; }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            background: #FFFFFF;
        }

        .page {
            width: 210mm;
            height: 297mm;
            background: #FFFFFF;
            padding: 8mm;
            font-family: "Times New Roman", Times, serif;
            color: #000;
        }

        .frame {
            width: 100%;
            height: 100%;
            border: 2px solid #000;
            padding: 10mm 12mm 12mm 12mm;
        }

        .header {
            text-align: center;
            font-family: Arial, Helvetica, sans-serif;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .header .line1 { font-size: 16pt; margin: 0; }
        .header .line2 { font-size: 14pt; margin: 1mm 0 0 0; }

        .crest {
            display: block;
            margin: 4mm auto 0 auto;
            width: 22mm;
            height: auto;
        }

        .content-wrap {
            width: 100%;
            margin-top: 6mm;
        }

        .main {
            width: 100%;
            border-collapse: collapse;
        }

        .main td { vertical-align: top; }

        .photo-cell {
            width: 24%;
            text-align: center;
            padding-top: 6mm;
        }

        .photo-cell img {
            width: 26mm;
            height: 32mm;
            object-fit: cover;
            border: 1px solid #666;
            background: #fff;
        }

        .body-cell { text-align: center; padding-top: 2mm; }
        .body-cell.with-photo { width: 76%; padding-left: 4mm; }
        .body-cell.no-photo { width: 100%; padding: 2mm 8mm 0 8mm; }

        .lead { font-size: 12pt; margin: 0 0 4mm 0; }
        .student-name {
            font-size: 20pt;
            font-style: italic;
            font-weight: bold;
            margin: 0 0 5mm 0;
            line-height: 1.2;
        }
        .bridge { font-size: 11.5pt; margin: 0 0 2mm 0; line-height: 1.35; }
        .degree-name {
            font-size: 14pt;
            font-style: italic;
            font-weight: bold;
            margin: 4mm 0;
            line-height: 1.35;
        }
        .class-line { font-size: 11.5pt; font-style: italic; margin: 3mm 0; }
        .institution { font-size: 11.5pt; margin: 3mm 0 4mm 0; }
        .date-line { font-size: 11.5pt; font-weight: bold; margin-top: 2mm; }

        .signatures {
            width: 100%;
            margin-top: 16mm;
            border-collapse: collapse;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 8mm;
        }

        .signatures img.vc {
            width: 44mm;
            height: auto;
            display: block;
            margin: 0 auto 1mm auto;
        }

        .signatures img.registrar {
            width: 42mm;
            height: auto;
            display: block;
            margin: 0 auto 1mm auto;
        }

        .sign-title {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            border-top: 1px solid #000;
            display: inline-block;
            min-width: 50mm;
            padding-top: 2mm;
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
                    @if ($vc_signature_data_uri)
                        <img class="vc" src="{{ $vc_signature_data_uri }}" alt="Vice Chancellor Signature">
                    @endif
                    <div class="sign-title">Vice Chancellor</div>
                </td>
                <td>
                    @if ($registrar_stamp_data_uri)
                        <img class="registrar" src="{{ $registrar_stamp_data_uri }}" alt="Registrar Stamp and Signature">
                    @endif
                    <div class="sign-title">Academic Registrar</div>
                </td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
