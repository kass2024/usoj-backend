<?php

/**
 * Remove cream/beige paper background from signature & stamp PNGs.
 */

function isPaperPixel(int $r, int $g, int $b): bool
{
    // Warm cream / parchment tones from scanned documents
    if ($r >= 210 && $g >= 200 && $b >= 170 && abs($r - $g) <= 25) {
        return true;
    }

    // Very light neutral paper
    if ($r >= 235 && $g >= 235 && $b >= 225) {
        return true;
    }

    // Slightly darker beige patches
    if ($r >= 190 && $g >= 175 && $b >= 140 && ($r - $b) <= 70) {
        return true;
    }

    return false;
}

function cleanSignatureImage(string $src, string $dest): bool
{
    if (!is_file($src)) {
        return false;
    }

    $img = @imagecreatefrompng($src);
    if (!$img) {
        $img = @imagecreatefromjpeg($src);
    }
    if (!$img) {
        return false;
    }

    $w = imagesx($img);
    $h = imagesy($img);
    $out = imagecreatetruecolor($w, $h);

    imagesavealpha($out, true);
    imagealphablending($out, false);

    $transparent = imagecolorallocatealpha($out, 255, 255, 255, 127);
    imagefill($out, 0, 0, $transparent);

    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $rgba = imagecolorat($img, $x, $y);
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;

            if (isPaperPixel($r, $g, $b)) {
                continue;
            }

            $a = ($rgba & 0x7F000000) >> 24;
            $color = imagecolorallocatealpha($out, $r, $g, $b, $a);
            imagesetpixel($out, $x, $y, $color);
        }
    }

    imagealphablending($out, false);
    imagesavealpha($out, true);
    imagepng($out, $dest);
    imagedestroy($img);
    imagedestroy($out);

    return true;
}

$dir = __DIR__ . '/../public/images/usoj';

$pairs = [
    'vc-signature.png' => 'vc-signature-clean.png',
    'degree-registrar-stamp.png' => 'degree-registrar-stamp-clean.png',
    'registrar-stamp.png' => 'registrar-stamp-clean.png',
];

foreach ($pairs as $srcName => $destName) {
    $src = "$dir/$srcName";
    $dest = "$dir/$destName";
    if (cleanSignatureImage($src, $dest)) {
        echo "Cleaned: $destName\n";
    }
}

// Also process user-provided crops if present in assets folder
$assetDir = dirname(__DIR__) . '/../.cursor/projects/c-methode-water-level-xander-learning-parrot-backend/assets';
$external = [
    'c__Users_user_AppData_Roaming_Cursor_User_workspaceStorage_3ea13f382d9b0195f311cac9bb861f4b_images_image-850a5cd1-4299-4ba7-a370-8eb3745fe7e6.png' => 'degree-registrar-stamp-clean.png',
];

foreach ($external as $file => $destName) {
    $path = "$assetDir/$file";
    if (is_file($path) && cleanSignatureImage($path, "$dir/$destName")) {
        echo "Cleaned from asset: $destName\n";
    }
}

echo "Done.\n";
