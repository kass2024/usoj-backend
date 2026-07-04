<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>USJ Degree Certificate</title>
    <style>
        @page { size: A4 portrait; margin: 0; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Times New Roman", Times, serif;
            color: #040611;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            position: relative;
            background: #F8E8C8;
            padding: 10mm 12mm 16mm 12mm;
        }

        .border-frame {
            border: 2px solid #D8C828;
            min-height: 277mm;
            padding: 8mm 10mm 12mm 10mm;
            position: relative;
        }

        .header {
            text-align: center;
            font-family: Arial, Helvetica, sans-serif;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .header h2 {
            margin: 1mm 0 0 0;
            font-size: 16px;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .crest {
            display: block;
            margin: 4mm auto 0 auto;
            width: 22mm;
            height: auto;
        }

        .layout {
            width: 100%;
            margin-top: 6mm;
        }

        .layout td { vertical-align: top; }

        .photo-cell {
            width: 24%;
            padding-right: 4mm;
        }

        .photo-cell img {
            width: 28mm;
            height: 34mm;
            object-fit: cover;
            border: 1px solid #888;
            background: #fff;
        }

        .body-cell {
            width: 76%;
            text-align: center;
            padding-top: 4mm;
        }

        .lead { font-size: 13px; margin: 0 0 4mm 0; }
        .student-name {
            font-size: 22px;
            font-style: italic;
            font-weight: bold;
            margin: 0 0 5mm 0;
        }
        .bridge { font-size: 12px; margin: 0 0 2mm 0; }
        .degree-name {
            font-size: 16px;
            font-style: italic;
            font-weight: bold;
            margin: 4mm 0;
            line-height: 1.35;
        }
        .class-line { font-size: 12px; font-style: italic; margin: 3mm 0; }
        .institution { font-size: 12px; margin: 3mm 0 4mm 0; }
        .date-line { font-size: 12px; font-weight: bold; margin-top: 3mm; }

        .signatures {
            position: absolute;
            left: 10mm;
            right: 10mm;
            bottom: 14mm;
        }

        .signatures table { width: 100%; border-collapse: collapse; }
        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 8mm;
        }

        .signatures img.vc { width: 42mm; height: auto; margin-bottom: 1mm; }
        .signatures img.registrar { width: 40mm; height: auto; margin-bottom: 1mm; }

        .sign-title {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            border-top: 1px solid #333;
            display: inline-block;
            min-width: 48mm;
            padding-top: 1.5mm;
        }
    </style>
</head>
<body>
<div class="page">
    <div class="border-frame">
        <div class="header">
            <h1>University of Saint Joseph</h1>
            <h2>Mbarara</h2>
            <img class="crest" src="{{ public_path('images/usj-crest.png') }}" alt="USJ Crest">
        </div>

        <table class="layout">
            <tr>
                <td class="photo-cell">
                    <img src="{{ $photo_path }}" alt="Student Photo">
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

        <div class="signatures">
            <table>
                <tr>
                    <td>
                        <img class="vc" src="{{ $vc_signature }}" alt="Vice Chancellor Signature">
                        <div class="sign-title">Vice Chancellor</div>
                    </td>
                    <td>
                        <img class="registrar" src="{{ $registrar_stamp }}" alt="Registrar Stamp">
                        <div class="sign-title">Academic Registrar</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
</body>
</html>
