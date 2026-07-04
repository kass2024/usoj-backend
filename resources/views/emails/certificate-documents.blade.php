<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Academic Documents</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 24px; }
        .header { color: #007a33; font-size: 20px; font-weight: bold; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">University of Saint Joseph Mbarara</div>

        <p>Dear Recipient,</p>

        <p>
            Please find attached the academic document(s) for
            <strong>{{ $student->fname }} {{ $student->lname }}</strong>
            (Registration No: <strong>{{ $student->reg_number }}</strong>).
        </p>

        @if ($documents === 'transcript')
            <p>Attached: <strong>Academic Transcript</strong></p>
        @elseif ($documents === 'degree')
            <p>Attached: <strong>Degree Certificate</strong></p>
        @else
            <p>Attached: <strong>Academic Transcript</strong> and <strong>Degree Certificate</strong></p>
        @endif

        <p>If you have any questions, contact us at <strong>uosj@uosj.ac.ug</strong>.</p>

        <p>
            Best regards,<br>
            <strong>Office of the Academic Registrar</strong><br>
            University of Saint Joseph Mbarara
        </p>
    </div>
</body>
</html>
