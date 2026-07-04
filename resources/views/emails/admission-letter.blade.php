<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>University of Saint Joseph Mbarara - Admission Letter</title>
  <style>
    body {
      font-family: 'Bookman Old Style', serif;
      margin: 0;
      padding: 0;
      background-color: #f4f4f4;
      font-size: 16px;
      text-align: justify;
    }

    .container {
      width: 210mm;
      max-width: 100%;
      margin: 30px auto;
      border: 2px solid #005a26;
      border-radius: 8px;
      background-color: #fff;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      position: relative;
      overflow: hidden;
    }

    .header {
      background-color: #007a33;
      color: #fff;
      padding: 15px;
      text-align: center;
      border-bottom: 2px solid #005a26;
    }

    .logo {
      height: 70px;
      width: auto;
      float: left;
      margin-right: 10px;
    }

    .header h2 {
      margin: 0;
      font-size: 24px;
    }

    .header p {
      margin: 0;
      font-size: 14px;
    }

    .content {
      padding: 20px;
      line-height: 1.6;
      position: relative;
      z-index: 1;
    }

    .content p {
      margin: 8px 0;
    }

    .qr-code {
      width: 100px;
      margin-top: 20px;
    }

    .issued-date {
      font-size: 12px;
      color: #333;
    }

    .footer {
      text-align: center;
      padding: 10px;
      background-color: #007a33;
      color: #fff;
      font-size: 12px;
      border-top: 2px solid #005a26;
    }

    .watermark {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) rotate(-45deg);
      font-size: 40px;
      color: rgba(0, 90, 38, 0.1);
      font-weight: bold;
      z-index: 0;
      white-space: nowrap;
      pointer-events: none;
    }
  </style>
</head>

<body>
  <div class="container">
    <!-- Watermark -->
    <div class="watermark">University of Saint Joseph Mbarara</div>

    <div class="header">
      <img src="{{ public_path('images/usj-crest.png') }}" alt="USJ Crest" class="logo">
      <h2>University of Saint Joseph Mbarara</h2>
      <p>www.uosj.ac.ug | uosj@uosj.ac.ug</p>
    </div>

    <div class="content">
      <p><strong>Names:</strong> {{ $student->fname }} {{ $student->lname }}</p>
      <p><strong>Reg. No:</strong> {{ $regNumber }}</p>
      <p><strong>Email:</strong> {{ $student->email }}</p>
      <p><strong>REF: Admission Letter</strong></p>
      <p>Dear Applicant,</p>

      <p>
        Congratulations! You have been officially admitted to <strong>University of Saint Joseph Mbarara</strong>. You
        have earned a place in the <strong>{{ $department->name }}</strong> department, where you will pursue a
        <strong>{{ $student->degree_level->name }}</strong> program.
      </p>

      <p>
        At our institution, you will gain valuable knowledge, hands-on experience, and the skills needed to excel in your field. Our dedicated faculty members and state-of-the-art learning resources are here to support you every step of the way.
      </p>

      <p>
        We encourage you to prepare for this new chapter by familiarizing yourself with your program requirements, engaging with fellow students, and making the most of the opportunities available to you.
      </p>

      <p>
        Welcome to <strong>University of Saint Joseph Mbarara</strong>! We look forward to seeing you thrive and achieve great success in your academic and professional journey.
      </p>

      <p><strong>Note:</strong> Present this letter during registration.</p>

      <center>
        <img class="qr-code" alt="USJ QR Code"
          src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(300)->generate("Name: $student->fname $student->lname | Reg No: $regNumber")) }}" />
       
       
      </center>
    </div>

    <div class="footer">
      &copy; {{ $student->date_created }} University of Saint Joseph Mbarara. All rights reserved.
    </div>
  </div>
</body>

</html>