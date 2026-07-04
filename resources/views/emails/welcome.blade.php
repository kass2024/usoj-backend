<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Letter Approved</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .header {
            font-size: 24px;
            font-weight: bold;
            color: #007a33;
            margin-bottom: 15px;
        }
        .content {
            text-align: left;
            line-height: 1.6;
        }
        .box {
            background: #e8f5e9;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #007a33;
            border-radius: 5px;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table, th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #007a33;
            color: white;
        }
        .button-container {
            margin-top: 20px;
        }
        .button {
            display: inline-block;
            padding: 12px 20px;
            background: #007a33;
            color: white;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
            font-size: 16px;
            transition: background 0.3s ease-in-out;
        }
        .button:hover {
            background: #005a26;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Admission Letter was Approved</div>

        <div class="content">
            <p>Dear <strong>{{ $student->fname }} {{ $student->lname }}</strong>,</p>
            <p>We are pleased to inform you that you have been successfully admitted to <strong>University of Saint Joseph Mbarara</strong>. Congratulations on your achievement!</p>
            
            <div class="box">
                <p><strong>Your Login Credentials:</strong></p>
                <table>
                    <tr>
                        <th>Detail</th>
                        <th>Information</th>
                    </tr>
                    <tr>
                        <td>Email</td>
                        <td>{{ $student->email }}</td>
                    </tr>
                    <tr>
                        <td>Password</td>
                        <td>{{ $password }}</td>
                    </tr>
                
                    <tr>
                        <td>Program</td>
                        <td>{{ $student->degree_level->name }}</td>
                    </tr>
                </table>
                <p><em>For security reasons, please change your password after logging in.</em></p>
            </div>

            <p>Click the button below to access your student portal:</p>
            
            <div class="button-container">
                <a href="{{ route('login') }}" class="button">Go to Student Portal</a>
            </div>

            <p>Through the portal, you can:</p>
            <ul>
                <li>Complete your student profile</li>
                <li>View your academic calendar</li>
                <li>Check course schedules and learning materials</li>
                <li>Stay updated with important announcements</li>
            </ul>

            <p><strong>Your admission letter is attached to this email.</strong> Please download and keep it for reference.</p>

            <p>If you have any questions, feel free to contact our Admissions Office at <strong>uosj@uosj.ac.ug</strong> or visit <strong>www.uosj.ac.ug</strong>.</p>

            <p>We look forward to welcoming you to our institution. Wishing you success in your academic journey!</p>

            <p>Best regards,<br>
           
            <strong>University of Saint Joseph Mbarara</strong><br>
            <em>Foster Excellence and Integrity</em></p>
        </div>
    </div>
</body>
</html>
