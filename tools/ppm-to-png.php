<?php

function ppmToPng(string $ppm, string $png): bool
{
    $fh = fopen($ppm, 'rb');
    if (!$fh) {
        return false;
    }

    if (trim(fgets($fh)) !== 'P6') {
        fclose($fh);
        return false;
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

    return true;
}

function cropPng(string $src, string $dest, int $x, int $y, int $w, int $h): bool
{
    $source = imagecreatefrompng($src);
    if (!$source) {
        return false;
    }

    $crop = imagecreatetruecolor($w, $h);
    imagealphablending($crop, false);
    imagesavealpha($crop, true);
    imagecopy($crop, $source, 0, 0, $x, $y, $w, $h);
    imagepng($crop, $dest);
    imagedestroy($source);
    imagedestroy($crop);

    return true;
}

$dir = __DIR__ . '/../public/images/usoj';
@mkdir($dir, 0775, true);

ppmToPng("$dir/transcript-1-000001.ppm", "$dir/transcript-reference.png");
ppmToPng("$dir/degree-1-000001.ppm", "$dir/degree-reference.png");

// Approximate crop regions from A4 scan (~1240x1754 at 150dpi) — stamp bottom-right, VC sig bottom-left on degree
$refW = 1240;
$refH = 1754;

cropPng("$dir/transcript-reference.png", "$dir/registrar-stamp.png", (int) ($refW * 0.68), (int) ($refH * 0.72), (int) ($refW * 0.22), (int) ($refH * 0.12));
cropPng("$dir/transcript-reference.png", "$dir/registrar-signature.png", (int) ($refW * 0.72), (int) ($refH * 0.68), (int) ($refW * 0.18), (int) ($refH * 0.08));
cropPng("$dir/degree-reference.png", "$dir/degree-registrar-stamp.png", (int) ($refW * 0.58), (int) ($refH * 0.78), (int) ($refW * 0.22), (int) ($refH * 0.12));
cropPng("$dir/degree-reference.png", "$dir/vc-signature.png", (int) ($refW * 0.12), (int) ($refH * 0.78), (int) ($refW * 0.28), (int) ($refH * 0.10));

echo "Converted reference assets in $dir\n";
