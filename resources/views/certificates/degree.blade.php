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
            height: 297mm;
            position: relative;
            background: linear-gradient(180deg, #F8E8C8 0%, #EFD9BB 45%, #F8E8C8 100%);
            overflow: hidden;
        }

        .inner-border {
            position: absolute;
            top: 8mm;
            left: 8mm;
            right: 8mm;
            bottom: 8mm;
            border: 2px solid rgba(232, 152, 40, 0.45);
        }

        .header {
            text-align: center;
            padding-top: 10mm;
            font-family: Arial, Helvetica, sans-serif;
        }

        .header h1 {
            margin: 0;
            font-size: 20px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .header h2 {
            margin: 1mm 0 0 0;
            font-size: 18px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .crest {
            display: block;
            margin: 4mm auto 2mm auto;
            width: 24mm;
            height: auto;
        }

        .photo-wrap {
            position: absolute;
            top: 52mm;
            left: 16mm;
            width: 30mm;
            text-align: center;
        }

        .photo-wrap img {
            width: 28mm;
            height: 34mm;
            object-fit: cover;
            border: 1px solid #888;
            background: #fff;
        }

        .body-copy {
            text-align: center;
            padding: 0 18mm 0 48mm;
            margin-top: 8mm;
        }

        .lead {
            font-size: 14px;
            margin: 0 0 4mm 0;
        }

        .student-name {
            font-size: 24px;
            font-style: italic;
            font-weight: bold;
            margin: 0 0 5mm 0;
        }

        .bridge {
            font-size: 13px;
            margin: 0 0 2mm 0;
        }

        .degree-name {
            font-size: 18px;
            font-style: italic;
            font-weight: bold;
            margin: 4mm 0;
            line-height: 1.35;
        }

        .class-line {
            font-size: 13px;
            font-style: italic;
            margin: 3mm 0;
        }

        .institution {
            font-size: 13px;
            margin: 3mm 0 5mm 0;
        }

        .date-line {
            font-size: 13px;
            font-weight: bold;
            margin-top: 4mm;
        }

        .signatures {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 22mm;
            width: 100%;
        }

        .signatures table {
            width: 100%;
            border-collapse: collapse;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 12mm;
        }

        .sign-line {
            border-top: 1px solid #333;
            width: 55mm;
            margin: 0 auto 2mm auto;
            height: 14mm;
        }

        .sign-title {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .registrar-stamp {
            width: 24mm;
            height: 24mm;
            border: 2px solid #6AA6B8;
            border-radius: 50%;
            color: #6AA6B8;
            font-size: 5.5px;
            font-weight: bold;
            line-height: 1.25;
            padding: 3mm 2mm;
            margin: 0 auto 2mm auto;
        }
    </style>
</head>
<body>
<div class="page">
    <div class="inner-border"></div>

    <div class="header">
        <h1>University of Saint Joseph</h1>
        <h2>Mbarara</h2>
        <img class="crest" src="{{ public_path('images/usj-crest.png') }}" alt="USJ Crest">
    </div>

    <div class="photo-wrap">
        <img src="{{ $photo_path }}" alt="Student Photo">
    </div>

    <div class="body-copy">
        <p class="lead">This is to certify that</p>
        <p class="student-name">{{ $student_name }}</p>
        <p class="bridge">has successfully completed a</p>
        <p class="bridge">Course of study leading to the award of</p>
        <p class="degree-name">{{ ucwords(strtolower($award)) }}</p>
        <p class="class-line">Class: {{ $degree_class }}</p>
        <p class="institution">of University Of Saint Joseph Mbarara</p>
        <p class="date-line">Date: {{ $issue_date }}</p>
    </div>

    <div class="signatures">
        <table>
            <tr>
                <td>
                    <div class="sign-line"></div>
                    <div class="sign-title">Vice Chancellor</div>
                </td>
                <td>
                    <div class="registrar-stamp">
                        UNIVERSITY OF SAINT JOSEPH<br>
                        ACADEMIC REGISTRAR<br>
                        Foster Excellence and Integrity
                    </div>
                    <div class="sign-line"></div>
                    <div class="sign-title">Academic Registrar</div>
                </td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
