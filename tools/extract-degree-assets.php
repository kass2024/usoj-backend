<?php

function ppmToPng(string $ppm, string $png): ?array
{
    $fh = fopen($ppm, 'rb');
    if (!$fh) {
        return null;
    }

    if (trim(fgets($fh)) !== 'P6') {
        fclose($fh);
        return null;
    }

    do {
        $line = fgets($fh);
    } while ($line !== false && ($line[0] === '#' || trim($line) === ''));

    [$w, $h] = array_map('intval', preg_split('/\s+/', trim($line)));

    do {
        $line = fgets($fh);
    } while ($line !== false && ($line[0] === '#' || trim($line) === ''));

    $bytes = fread($fh, $w * $h * 3);
    fclose($fh);

    $img = imagecreatetruecolor($w, $h);
    $i = 0;

    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $r = ord($bytes[$i++]);
            $g = ord($bytes[$i++]);
            $b = ord($bytes[$i++]);
            imagesetpixel($img, $x, $y, imagecolorallocate($img, $r, $g, $b));
        }
    }

    imagepng($img, $png);
    imagedestroy($img);

    return ['width' => $w, 'height' => $h];
}

function cropPng(string $src, string $dest, int $x, int $y, int $w, int $h): bool
{
    $source = imagecreatefrompng($src);
    if (!$source) {
        return false;
    }

    $crop = imagecreatetruecolor($w, $h);
    imagecopy($crop, $source, 0, 0, $x, $y, $w, $h);
    imagepng($crop, $dest);
    imagedestroy($source);
    imagedestroy($crop);

    return true;
}

$dir = __DIR__ . '/../public/images/usoj';
$ppm = "$dir/degree-ref-000001.ppm";
$full = "$dir/degree-reference-200.png";

if (!is_file($ppm)) {
    fwrite(STDERR, "Missing $ppm\n");
    exit(1);
}

$size = ppmToPng($ppm, $full);
if (!$size) {
    fwrite(STDERR, "Failed to convert PPM\n");
    exit(1);
}

$w = $size['width'];
$h = $size['height'];

// Vice Chancellor signature — bottom-left (no title text)
cropPng(
    $full,
    "$dir/vc-signature.png",
    (int) ($w * 0.10),
    (int) ($h * 0.808),
    (int) ($w * 0.30),
    (int) ($w * 0.045)
);

// Registrar circular stamp ONLY — circle without title text below
cropPng(
    $full,
    "$dir/registrar-stamp-only.png",
    (int) ($w * 0.565),
    (int) ($h * 0.795),
    (int) ($w * 0.175),
    (int) ($w * 0.175)
);

// Registrar blue signature ONLY — separate from stamp
cropPng(
    $full,
    "$dir/registrar-signature-only.png",
    (int) ($w * 0.58),
    (int) ($h * 0.818),
    (int) ($w * 0.28),
    (int) ($w * 0.045)
);

echo "Extracted degree assets at {$w}x{$h}\n";
