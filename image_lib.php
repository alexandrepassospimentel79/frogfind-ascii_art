<?php
/*
 * Shared helpers for the alternative image views (ASCII art, B&W).
 */

/*
 * Downloads and decodes an image.
 * Returns the GD image on success, or an HTTP error code (int) on failure.
 */
function frog_load_image($url) {
    //an image will start with http(s), anything else is sus
    if (!preg_match('#^https?://#i', $url)) {
        return 400;
    }

    //don't let this be used to poke at the local network
    $host = parse_url($url, PHP_URL_HOST);
    $ip   = $host ? gethostbyname($host) : '';
    if (!$host || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return 400;
    }

    //download (some servers reject requests without a User-Agent)
    $context = stream_context_create(array(
        'http' => array(
            'header'  => "User-Agent: Mozilla/5.0 (compatible; FrogFind/1.0)\r\n",
            'timeout' => 15,
        )
    ));
    $data = @file_get_contents($url, false, $context, 0, 10000001);
    if ($data === false || strlen($data) > 10000000) {
        return 502;
    }

    $img = @imagecreatefromstring($data);
    if (!$img) {
        return 415;
    }
    return $img;
}

/*
 * Converts a GD image to ASCII art (a string, one line per row).
 *
 * $cols     - width in characters (use screen width minus a small margin)
 * $inverted - false (default) = for dark text on a light background:
 *             the darker the image, the "heavier" the character.
 *             true = for light text on a dark background (most terminals):
 *             the brighter the image, the "heavier" the character.
 */
function frog_ascii_art($img, $cols = 78, $inverted = false) {
    $ramp = " .:-=+*#%@";
    $n    = strlen($ramp) - 1;

    $src_w = imagesx($img);
    $src_h = imagesy($img);

    //characters are roughly twice as tall as wide, so use half the rows
    $rows = max(1, (int)round($src_h / $src_w * $cols * 0.5));

    //white background first, so transparent PNGs don't turn black
    $small = imagecreatetruecolor($cols, $rows);
    imagefill($small, 0, 0, imagecolorallocate($small, 255, 255, 255));
    imagecopyresampled($small, $img, 0, 0, 0, 0, $cols, $rows, $src_w, $src_h);

    $luma = array();
    $hist = array_fill(0, 256, 0);
    for ($y = 0; $y < $rows; $y++) {
        for ($x = 0; $x < $cols; $x++) {
            $c = imagecolorat($small, $x, $y);
            $v = 0.299 * (($c >> 16) & 255) + 0.587 * (($c >> 8) & 255) + 0.114 * ($c & 255);
            $luma[$y * $cols + $x] = $v;
            $hist[(int)$v]++;
        }
    }

    //auto-levels: stretch the 2%..98% range so dull images still show detail
    $total = $cols * $rows;
    $lo = 0; $hi = 255; $acc = 0;
    for ($i = 0; $i < 256; $i++) { $acc += $hist[$i]; if ($acc >= $total * 0.02) { $lo = $i; break; } }
    $acc = 0;
    for ($i = 255; $i >= 0; $i--) { $acc += $hist[$i]; if ($acc >= $total * 0.02) { $hi = $i; break; } }
    if ($hi - $lo < 16) { $lo = 0; $hi = 255; }

    $out = "";
    for ($y = 0; $y < $rows; $y++) {
        for ($x = 0; $x < $cols; $x++) {
            $v = ($luma[$y * $cols + $x] - $lo) / ($hi - $lo);
            $v = max(0, min(1, $v));
            if (!$inverted) {
                $v = 1 - $v; //dark areas get the heavy characters
            }
            $out .= $ramp[(int)round($v * $n)];
        }
        $out .= "\n";
    }
    return $out;
}
