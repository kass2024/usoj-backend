<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic degree</title>
    <style>
        * {
            margin: 0;
        }
    </style>
</head>

<body
      style="background: url('file://{{ public_path('images/degree-bg.png') }}') no-repeat center center; background-size: contain; position: relative;">


    <!-- degre name -->
    <h1
        style="position: absolute; top: 250px; left: 50%; transform: translateX(-50%); text-align: center; opacity: .7; font-weight: bolder; ">
        {{ strtoupper($student->degree_level->name) }}
    </h1>

    <!-- student name -->
    <h2
        style="position: absolute; top: 300px; left: 50%; transform: translateX(-50%); text-align: center; opacity: .7; font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif; ">
        <b>
            <i>{{ strtoupper($student->fname) }}
                {{ ucfirst(strtolower($student->lname)) }}</i>
        </b>
    </h2>

    <!-- department -->

    <h3 style="
    position: absolute;
    top: 390px;
    width: 100%;
    font-style: italic;
    text-align: center;
    font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
    text-transform: uppercase;
    ">
        {{ strtoupper(substr($student->degree_level->name, 0, 8))  }}
        OF SCIENCE IN
        {{ $student->department->name }}
    </h3>

    <!-- date -->

    <p style="position: absolute; top: 457px; left: 740px;  opacity: .7; ">
        <b>
            <i>
                {{ now()->format("d") }}<sup>th</sup> {{ now()->format("F") }}
                {{ now()->format("Y") }}
            </i>
        </b>
    </p>

    <!-- performance category -->
    @php
        $cummulativeMarks = 0;
        $numberYears = 0;
    @endphp

    @foreach ($courses as $academicYear => $courseData)

        @php
            $i = 0;
            $totalPercentage = 0;
            $totalCredMax = 0;
            $numberYears++;
        @endphp
        @foreach ($courseData as $course)
            @php
                $i++;
                $totalPercentage += $course['percentage'];
                $totalCredMax += $course['credit_max'];
            @endphp

        @endforeach
        @php
            $cummulativeMarks += $totalPercentage;
        @endphp
    @endforeach
    <!-- calculate grades -->
    @php
        $grade = $cummulativeMarks / $numberYears;
        // calculate performance class
        $class = "";
        switch (true) {
            case $grade >= 80:
                $class = "First class";
                break;

            case $grade >= 70:
                $class = "Second class upper division";
                break;

            case $grade >= 60:
                $class = "Second class lower division";
                break;

            case $grade >= 50:
                $class = "Pass";
                break;

            default:
                $class = "Fail";
                break;
        }
     @endphp
    <p style="position: absolute; top: 420px; left: 50%; transform: translateX(-50%); text-align: center;">
        <b><i>({{ $class }})</i></b>
    </p>

    <!-- index number -->
    <p
       style="position: absolute; bottom: 84px; left: 50%; transform: translateX(-50%); text-align: center; opacity: .7;">
        <b><i>{{ strtoupper($student->reg_number) }}</i></b>
    </p>
</body>

</html>