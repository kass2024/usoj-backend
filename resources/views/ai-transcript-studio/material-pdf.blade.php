<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #222; line-height: 1.5; }
        h1 { color: #007a33; font-size: 16pt; border-bottom: 2px solid #007a33; padding-bottom: 6px; }
        h2 { color: #005a26; font-size: 13pt; margin-top: 18px; }
        .meta { font-size: 9pt; color: #666; margin-bottom: 16px; }
        .footer { margin-top: 30px; font-size: 8pt; color: #888; text-align: center; }
    </style>
</head>
<body>
    <h1>{{ $course->code }} — {{ $course->name }}</h1>
    <div class="meta">
        University of Saint Joseph Mbarara · AI-generated course material · {{ now()->format('d M Y') }}
    </div>
    {!! $content !!}
    <div class="footer">USJ E-Learning · Auto-generated for academic records</div>
</body>
</html>
