<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "PROGRAMS\n";
foreach (App\Models\Program::all() as $p) {
    echo "{$p->id} | {$p->name} | {$p->status}\n";
}
echo "DEGREE_LEVELS\n";
foreach (App\Models\DegreeLevel::all() as $d) {
    echo "{$d->id} | {$d->name} | program_id={$d->program_id} | {$d->status}\n";
}
echo "DEPARTMENTS\n";
foreach (App\Models\Department::with(['school.program'])->get() as $dep) {
    $school = $dep->school->name ?? '-';
    $prog = $dep->school->program->name ?? '-';
    echo "{$dep->id} | {$dep->name} | school={$school} | program={$prog} | status={$dep->status}\n";
}
echo "SCHOOLS\n";
foreach (App\Models\School::with('program')->get() as $s) {
    echo "{$s->id} | {$s->name} | program=".($s->program->name??'-')."\n";
}
echo "WEBSITE_PROGRAMMES\n";
foreach (App\Models\WebsiteProgramme::all() as $w) {
    echo "{$w->id} | {$w->name} | {$w->category}\n";
}
