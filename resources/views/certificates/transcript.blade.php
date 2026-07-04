<!DOCTYPE html>
<html>
<head>
    <title>Transcript</title>
    <style>
        /* Dompdf page */
        @page { size: A4 portrait; margin: 0; }

        :root{
            /* Header/footer geometry */
            --header-h: 38mm;      /* height reserved for header image */
            --footer-h: 27mm;      /* height reserved for footer image */
            --side-pad: 10mm;      /* horizontal padding for content */

            /* Spacing tweaks */
            --note-gap: 3.5mm;     /* space between footer-note and footer image */
        }

        body { margin: 0; font-family: Arial, sans-serif; font-size: 12px; }

        /* One physical page */
        .page{
            position: relative;
            width: 210mm;
            height: 297mm;
            page-break-after: always;
            overflow: hidden;
        }
        .page:last-of-type { page-break-after: auto; }

        /* Header/Footer blocks */
        .page__header,
        .page__footer{
            position: absolute;
            left: 0; width: 100%;
        }
        .page__header{ top: 0; height: var(--header-h); }

        /* Footer locked at bottom edge */
        .page__footer{
            bottom: 0;
            height: var(--footer-h);
        }

        .page__header img,
        .page__footer img{
            width: 100%;
            height: 100%;
            display: block;
            object-fit: contain;          /* Avoid cropping; keep proportions */
            object-position: center;      /* Center the art */
        }

        /* Content between header and footer */
        .page__content{
            position: absolute;
            top: var(--header-h);
            bottom: var(--footer-h);
            left: 0; right: 0;
            padding: 0 var(--side-pad);
        }

        .header{ text-align: center; margin: 6mm 0 4mm 0; }

        .meta{
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-column-gap: 8mm;
            grid-row-gap: 2mm;
            margin-bottom: 6mm;
        }
        .meta p{ margin: 0; }
        .bold{ font-weight: bold; }

        /* Section title shows YEAR (not academic year) */
        .year-title{
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            margin: 6mm 0 3mm 0;
            border: 1px solid #000;
        }

        table{ width:100%; border-collapse: collapse; table-layout: fixed; }
        table, th, td{ border:1px solid #000; }
        th, td{ padding:5px; text-align:center; word-wrap: break-word; }
        td.text-left{ text-align:left; }

        /* Footer note sits ABOVE the footer image */
        .footer-note{
            position: absolute;
            right: var(--side-pad);
            bottom: calc(var(--footer-h) + var(--note-gap));
            text-align: right;

            /* Use Times-style font as in your sample */
            font-family: "Times New Roman", Times, serif;
            font-size: 12px;
            line-height: 1.2;
        }

        .avoid-break{ break-inside: avoid; }
    </style>
</head>
<body>

@php
    /* === Academic Year labels (for header/meta only) === */
    $academicYearLabels = [];
    $reg = (string)($student->reg_number ?? '');
    if (\Illuminate\Support\Str::startsWith($reg, '20')) {
        $baseStart = 2020; // Year 1 -> 2020-2021 (adjust if needed)
        for ($i = 0; $i < 4; $i++) {
            $start = $baseStart + $i;
            $end   = $start + 1;
            $academicYearLabels[$i + 1] = "{$start}-{$end}";
        }
    }

    /* === Optional specialization lookup per department ===
       Add more entries as needed: 'Department Name' => '(Speciality ...)'
    */
    $specialityByDepartment = [
        'Mechanical and Production Engineering' => '(Speciality Automotive and Power Engineering)',
        // 'Electrical Engineering' => '(Speciality Power Systems)',
        // 'Computer Science' => '(Speciality Software Engineering)',
    ];

    // Helper to get normalized department name
    $deptName = trim((string)optional($student->department)->name);
    $specialityLine = $specialityByDepartment[$deptName] ?? null;
@endphp

@php $__yearIndex = 0; @endphp
@foreach ($courses as $__originalKey => $courseData)
    @php
        $__yearIndex++;

        /* Header shows Academic Year (computed if reg starts with 20, else fallback) */
        $academicYear = $academicYearLabels[$__yearIndex] ?? $__originalKey;

        /* Section title shows just Year N */
        $yearLabel = "Year " . $__yearIndex;

        /* Totals for this page */
        $i = 0; $totalPercentage = 0; $totalCredMax = 0;
    @endphp

    <div class="page">
        <!-- Header image -->
        <div class="page__header">
            <img src="{{ public_path('/images/transcript-header.png') }}" alt="Header">
        </div>

        <!-- Main content -->
        <div class="page__content">
            <div class="header">
                <h3 style="margin:0;">ACADEMIC TRANSCRIPT</h3>
            </div>

            <!-- Student meta (Academic Year here) -->
            <div class="meta">
                <p><span class="bold">Name:</span> {{ $student->fname }}</p>
                <p><span class="bold">Surname:</span> {{ $student->lname }}</p>
                <p><span class="bold">Reg.Number:</span> {{ $student->reg_number }}</p>
                <p><span class="bold">Academic Year:</span> {{ $academicYear }}</p>
                <p><span class="bold">Option:</span> {{ $deptName }}</p>
                <p><span class="bold">Class:</span> {{ $yearLabel }}</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width:14%;">Code</th>
                        <th style="width:36%;">Course Name</th>
                        <th style="width:10%;">Credits</th>
                        <th style="width:12%;">Marks/20</th>
                        <th style="width:14%;">Credit&nbsp;Max</th>
                        <th style="width:14%;">Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($courseData as $course)
                        @php
                            $i++;
                            $totalPercentage += $course['percentage'];
                            $totalCredMax += $course['credit_max'];
                        @endphp
                        <tr>
                            <td>{{ $course['code'] }}</td>
                            <td class="text-left">{{ $course['name'] }}</td>
                            <td>{{ $course['credits'] }}</td>
                            <td>{{ $course['marks'] }}</td>
                            <td>{{ $course['credit_max'] }}</td>
                            <td>{{ $course['percentage'] }}</td>
                        </tr>
                    @endforeach

                    <!-- Totals -->
                    <tr class="avoid-break">
                        <td colspan="2"></td><td></td><td></td>
                        <td><b>{{ $totalCredMax }}</b></td>
                        <td><b>{{ round($totalPercentage / max($i,1), 2) }}</b></td>
                    </tr>

                    <!-- Verdict -->
                    <tr class="avoid-break">
                        <td>Verdict</td>
                        @if ($__yearIndex == 4)
                            <td colspan="5" style="font-weight:bold">
                                Promoted to {{ $student->degree_level->name }} in {{ $deptName }}
                               
                                   
                           
                            </td>
                        @else
                            <td colspan="5" style="font-weight:bold">Promoted</td>
                        @endif
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer note (Times New Roman style) -->
        <div class="footer-note">
            <p style="margin:0;">Ouagadougou, {{ now()->format('d') }}<sup>th</sup> {{ now()->format('F Y') }}</p>
            <p style="margin:2mm 0 0 0;">Director General</p>
            <p style="margin:1mm 0 0 0;"><b>Dr Issa COMPAORE</b></p>
            <p style="margin:1mm 0 0 0;">Officer of Came's International Order of Academic Palms</p>
        </div>

        <!-- Footer image (fixed at bottom) -->
        <div class="page__footer">
            <img src="{{ public_path('/images/transcript-footer.png') }}" alt="Footer">
        </div>
    </div>
@endforeach

</body>
</html>
