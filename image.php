<?php

    $url = "";
    
    //get the image url
    if (isset( $_GET['i'] ) ) {
        $url = $_GET[ 'i' ];
    } else {
        exit();
    }

    //we can only do jpg and png here
    if (strpos($url, ".jpg") && strpos($url, ".jpeg") && strpos($url, ".png") != true ) {
        echo strpos($url, ".jpg");
        echo "Unsupported file type :(";
        exit();
    }

    //image needs to start with http
    if (substr( $url, 0, 4 ) != "http") {
        echo("Image failed :(");
        exit();
    }

    //which rendering to show (default = the original compressed image)
    //ASCII modes: width (80/40 columns), "i" = inverted
    //(inverted = light text on a dark background; the default is dark on light)
    //(text is made 2 columns narrower than the screen, so lines don't wrap)
    //each entry: columns, inverted
    $ascii_modes = array(
        "ascii80"  => array(78, false),
        "ascii80i" => array(78, true),
        "ascii40"  => array(38, false),
        "ascii40i" => array(38, true),
    );
    $mode = isset( $_GET['m'] ) ? $_GET['m'] : "";
    if ($mode != "bw" && !isset($ascii_modes[$mode])) {
        $mode = "";
    }

    //keep the "back" link pointing at the article, even after clicking an option
    if (isset( $_GET['r'] )) {
        $back = $_GET['r'];
    } else {
        $back = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : "/";
    }
    $opt = "image.php?i=" . urlencode($url) . "&amp;r=" . urlencode($back);

    //a link to another mode, or just the label in bold if it's the current one
    function mode_link($opt, $current, $m, $label) {
        if ($current == $m) {
            return "<b>[$label]</b>";
        }
        return "<a href=\"$opt" . ($m == "" ? "" : "&amp;m=$m") . "\">[$label]</a>";
    }

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 2.0//EN">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 
 
 <html>
 <head>
     <title>FrogFind Image Viewer</title>
 </head>
 <body">
    <small><a href="<?php echo htmlspecialchars($back) ?>">< Back to previous page</a></small>
    <p><small><b>Viewing image:</b> <?php echo $url ?></small></p>
    <p><small><b>View as:</b>
        <?php echo mode_link($opt, $mode, "", "Original"); ?> |
        <?php echo mode_link($opt, $mode, "bw", "Black &amp; white"); ?>
        <br>
        <b>ASCII art (text only):</b> Normal = dark text on light background, Inverted = light text on dark background
        <br>
        80 cols: <?php echo mode_link($opt, $mode, "ascii80", "Normal"); ?>
        <?php echo mode_link($opt, $mode, "ascii80i", "Inverted"); ?>
        <br>
        40 cols: <?php echo mode_link($opt, $mode, "ascii40", "Normal"); ?>
        <?php echo mode_link($opt, $mode, "ascii40i", "Inverted"); ?>
    </small></p>
    <?php if ($mode == "") { ?>
    <img src="/image_compressed.php?i=<?php echo $url; ?>">
    <?php } else if (isset($ascii_modes[$mode])) {
        //ASCII art is plain text, so it works in text-only browsers too
        require_once('image_lib.php');
        $ascii_img = frog_load_image($url);
        if (is_int($ascii_img)) {
            echo "Could not make the ASCII art :(";
        } else {
            echo "<pre>" . htmlspecialchars(frog_ascii_art($ascii_img, $ascii_modes[$mode][0], $ascii_modes[$mode][1])) . "</pre>";
        }
    } else { ?>
    <img src="/image_filter.php?i=<?php echo urlencode($url); ?>&m=<?php echo $mode; ?>">
    <?php } ?>
    <br><br>
    <small><a href="<?php echo htmlspecialchars($back) ?>">< Back to previous page</a></small>
 </body>
 </html>