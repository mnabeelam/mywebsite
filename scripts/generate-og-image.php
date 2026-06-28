<?php
declare(strict_types=1);

/**
 * Generate assets/og-image.png (1200x630) for social sharing previews.
 * Requires Python Pillow: pip install pillow
 */
$root = dirname(__DIR__);
$script = <<<'PY'
from PIL import Image, ImageDraw, ImageFont

w, h = 1200, 630
img = Image.new("RGB", (w, h), (15, 23, 42))
draw = ImageDraw.Draw(img)
draw.rounded_rectangle((40, 40, w - 40, h - 40), radius=24, outline=(56, 189, 248), width=4)

try:
    title_font = ImageFont.truetype("arial.ttf", 42)
    subtitle_font = ImageFont.truetype("arial.ttf", 34)
    body_font = ImageFont.truetype("arial.ttf", 28)
    foot_font = ImageFont.truetype("arial.ttf", 24)
except OSError:
    title_font = subtitle_font = body_font = foot_font = ImageFont.load_default()

draw.text((80, 120), "Mirza Nabeel Ahmed", fill=(56, 189, 248), font=title_font)
draw.text((80, 190), "Deputy Director IT", fill=(226, 232, 240), font=subtitle_font)
draw.text((80, 260), "Oracle · VMware · AI · Cyber Security · Smart Campus", fill=(148, 163, 184), font=body_font)
draw.text((80, 520), "Executive IT Portfolio", fill=(100, 116, 139), font=foot_font)

img.save(r"__OUT__")
print("Created og-image.png")
PY;

$script = str_replace('__OUT__', str_replace('\\', '\\\\', $root . '/assets/og-image.png'), $script);
$tmp = tempnam(sys_get_temp_dir(), 'ogimg');
file_put_contents($tmp, $script);
passthru('python ' . escapeshellarg($tmp), $code);
@unlink($tmp);
exit($code === 0 ? 0 : 1);
