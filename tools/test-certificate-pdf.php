<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;

$reg = $argv[1] ?? '21MEE001';
$student = Student::with(['department.school', 'degree_level'])
    ->whereRaw('LOWER(reg_number) = ?', [strtolower($reg)])
    ->first();

if (!$student) {
    fwrite(STDERR, "Student not found: $reg\n");
    exit(1);
}

$controller = app(\App\Http\Controllers\CertificatesController::class);
$reflection = new ReflectionClass(\App\Http\Controllers\CertificatesController::class);
$method = $reflection->getMethod('buildCertificateData');
$method->setAccessible(true);
$data = $method->invoke($controller, $student, true);

$outDir = __DIR__ . '/../storage/app/test-pdfs';
if (!is_dir($outDir)) {
    mkdir($outDir, 0775, true);
}

$degree = Pdf::loadView('certificates.degree', $data)
    ->setPaper('a4', 'portrait')
    ->setOptions(['isRemoteEnabled' => true, 'dpi' => 150]);

$degreePath = "$outDir/{$reg}_degree.pdf";
file_put_contents($degreePath, $degree->output());
echo "Degree: $degreePath\n";

try {
    $transcript = Pdf::loadView('certificates.transcript', $data)
        ->setPaper('a4', 'portrait')
        ->setOptions(['isRemoteEnabled' => true, 'dpi' => 150]);

    $transcriptPath = "$outDir/{$reg}_transcript.pdf";
    file_put_contents($transcriptPath, $transcript->output());
    echo "Transcript: $transcriptPath\n";
} catch (Throwable $e) {
    echo "Transcript skipped: " . $e->getMessage() . "\n";
}
