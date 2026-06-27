<?php
declare(strict_types=1);

if (!extension_loaded('gd')) {
    fwrite(STDERR, "GD extension required to generate PWA icons.\n");
    exit(1);
}

$root = dirname(__DIR__);
$sizes = [192, 512];

foreach ($sizes as $size) {
    $img = imagecreatetruecolor($size, $size);
    if ($img === false) {
        continue;
    }

    imagealphablending($img, true);
    imagesavealpha($img, true);

    $bg = imagecolorallocate($img, 7, 11, 20);
    $accent = imagecolorallocate($img, 0, 212, 255);
    $inner = imagecolorallocate($img, 34, 197, 94);

    imagefilledrectangle($img, 0, 0, $size, $size, $bg);
    imagefilledellipse($img, (int) ($size / 2), (int) ($size / 2), (int) ($size * 0.72), (int) ($size * 0.72), $accent);
    imagefilledellipse($img, (int) ($size / 2), (int) ($size / 2), (int) ($size * 0.34), (int) ($size * 0.34), $inner);

    $path = $root . '/assets/icon-' . $size . '.png';
    imagepng($img, $path);
    imagedestroy($img);
    echo "Created {$path}\n";
}
