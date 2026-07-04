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
            background: #FDF5E6;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            background: #FDF5E6;
            padding: 14mm 16mm 18mm 16mm;
            font-family: "Times New Roman", Times, serif;
            color: #0E0603;
        }

        .header {
            text-align: center;
            font-family: Arial, Helvetica, sans-serif;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .header .line1 { font-size: 17pt; margin: 0; }
        .header .line2 { font-size: 15pt; margin: 1mm 0 0 0; }

        .crest {
            display: block;
            margin: 5mm auto 0 auto;
            width: 24mm;
            height: auto;
        }

        .main {
            width: 100%;
            margin-top: 8mm;
            border-collapse: collapse;
        }

        .main td { vertical-align: top; }

        .photo-cell {
            width: 22%;
            text-align: center;
            padding-top: 8mm;
        }

        .photo-cell img {
            width: 28mm;
            height: 34mm;
            object-fit: cover;
            border: 1px solid #999;
            background: #fff;
        }

        .body-cell {
            width: 78%;
            text-align: center;
            padding: 4mm 2mm 0 4mm;
        }

        .lead {
            font-size: 12.5pt;
            margin: 0 0 5mm 0;
        }

        .student-name {
            font-size: 21pt;
            font-style: italic;
            font-weight: bold;
            margin: 0 0 6mm 0;
            line-height: 1.2;
        }

        .bridge {
            font-size: 12pt;
            margin: 0 0 2.5mm 0;
            line-height: 1.35;
        }

        .degree-name {
            font-size: 15pt;
            font-style: italic;
            font-weight: bold;
            margin: 5mm 0;
            line-height: 1.35;
        }

        .class-line {
            font-size: 12pt;
            font-style: italic;
            margin: 4mm 0;
        }

        .institution {
            font-size: 12pt;
            margin: 4mm 0 5mm 0;
        }

        .date-line {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 3mm;
        }

        .signatures {
            width: 100%;
            margin-top: 28mm;
            border-collapse: collapse;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 6mm;
        }

        .signatures img.vc {
            width: 48mm;
            height: auto;
            display: block;
            margin: 0 auto 2mm auto;
        }

        .signatures img.registrar {
            width: 46mm;
            height: auto;
            display: block;
            margin: 0 auto 2mm auto;
        }

        .sign-title {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            border-top: 1px solid #222;
            display: inline-block;
            min-width: 52mm;
            padding-top: 2mm;
        }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <p class="line1">University of Saint Joseph</p>
        <p class="line2">Mbarara</p>
        @if ($crest_data_uri)
            <img class="crest" src="{{ $crest_data_uri }}" alt="USJ Crest">
        @endif
    </div>

    <table class="main">
        <tr>
            <td class="photo-cell">
                @if ($photo_data_uri)
                    <img src="{{ $photo_data_uri }}" alt="Student Photo">
                @endif
            </td>
            <td class="body-cell">
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
</body>
</html>
