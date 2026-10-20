<?php
/*
 * Generates a black & white (1-bit, dithered) rendering of an image.
 *
 *   image_filter.php?i=IMAGE_URL&m=bw
 *
 * (ASCII art is text, so it is rendered directly inside image.php.)
 */
require_once('image_lib.php');

$url  = isset($_GET['i']) ? $_GET['i'] : '';
$mode = isset($_GET['m']) ? $_GET['m'] : '';

if ($mode !== 'bw') {
    http_response_code(400);
    exit();
}

$raw_image = frog_load_image($url);
if (is_int($raw_image)) {
    http_response_code($raw_image);
    exit();
}

$raw_w = imagesx($raw_image);
$raw_h = imagesy($raw_image);

//longest side, in pixels, of the generated image
$max_side = 300;

if ($raw_w >= $raw_h) {
    $w = min($max_side, $raw_w);
    $h = max(1, intval($raw_h / $raw_w * $w));
} else {
    $h = min($max_side, $raw_h);
    $w = max(1, intval($raw_w / $raw_h * $h));
}

//resample onto a white background (so transparent PNGs don't turn black)
$small = imagecreatetruecolor($w, $h);
imagefill($small, 0, 0, imagecolorallocate($small, 255, 255, 255));
imagecopyresampled($small, $raw_image, 0, 0, 0, 0, $w, $h, $raw_w, $raw_h);
imagedestroy($raw_image);

//---- work on a grayscale (luma) map ----

$luma = array();
$hist = array_fill(0, 256, 0);
for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $c = imagecolorat($small, $x, $y);
        $v = 0.299 * (($c >> 16) & 255) + 0.587 * (($c >> 8) & 255) + 0.114 * ($c & 255);
        $luma[$y * $w + $x] = $v;
        $hist[(int)$v]++;
    }
}

//auto-levels: stretch the 2%..98% range to 0..255 so dull images get contrast
$total = $w * $h;
$lo = 0; $hi = 255; $acc = 0;
for ($i = 0; $i < 256; $i++) { $acc += $hist[$i]; if ($acc >= $total * 0.02) { $lo = $i; break; } }
$acc = 0;
for ($i = 255; $i >= 0; $i--) { $acc += $hist[$i]; if ($acc >= $total * 0.02) { $hi = $i; break; } }
if ($hi - $lo < 16) { $lo = 0; $hi = 255; }

foreach ($luma as $k => $v) {
    $v = ($v - $lo) / ($hi - $lo) * 255;
    $luma[$k] = max(0, min(255, $v));
}

$out   = imagecreate($w, $h);
$black = imagecolorallocate($out, 0, 0, 0);
$white = imagecolorallocate($out, 255, 255, 255);

//Floyd-Steinberg dithering: spreads the rounding error to neighbours
for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $i   = $y * $w + $x;
        $old = $luma[$i];
        $new = $old > 127 ? 255 : 0;
        imagesetpixel($out, $x, $y, $new ? $white : $black);
        $err = $old - $new;
        if ($x + 1 < $w)                 { $luma[$i + 1]      += $err * 7 / 16; }
        if ($y + 1 < $h) {
            if ($x > 0)                  { $luma[$i + $w - 1] += $err * 3 / 16; }
                                           $luma[$i + $w]     += $err * 5 / 16;
            if ($x + 1 < $w)             { $luma[$i + $w + 1] += $err * 1 / 16; }
        }
    }
}

//GIF instead of PNG: the oldest browsers can't read PNG, but all read GIF
header('Content-Type: image/gif');
imagegif($out);
