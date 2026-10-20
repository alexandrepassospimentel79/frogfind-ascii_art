<?php
require_once('vendor/autoload.php');

$article_url = "";
$article_html = "";
$error_text = "";

// List of Content-Types that we know we can (try to) parse. 
// Anything else will get piped through directly, if possible.
$compatible_content_types = [
    "text/html",
    "text/plain"
];

// The maximum allowed filesize for proxy download passthroughs. 
// Any file larger than this will instead show an error message, with
// a direct link to the file.
$proxy_download_max_filesize = 8000000; // ~ 8Mb

if( isset( $_GET['a'] ) ) {
    $article_url = $_GET["a"];
} else {
    echo "What do you think you're doing... >:(";
    exit();
}

if (substr( $article_url, 0, 4 ) != "http") {
    echo("That's not a web page :(");
    die();
}

$url = parse_url($article_url);
$host = $url['host'];

// Attempt to figure out what the requested URL content-type may be
// (some sites, e.g. Wikipedia, reject requests that have no User-Agent)
$context = stream_context_create(['http' => array(
    'method' => 'HEAD',
    'header' => "User-Agent: Mozilla/5.0 (compatible; FrogFind/1.0)\r\n"
)]);
$headers = get_headers($article_url, true, $context);
// Servers may return header names in any case (e.g. HTTP/2 uses lowercase)
$headers = $headers ? array_change_key_case($headers, CASE_LOWER) : array();

// Content-Length is optional (e.g. chunked or compressed responses don't send it)
if (!array_key_exists('content-type', $headers)) {
    $error_text .=  "Failed to get the article, its server did not return expected details :( <br>";
}
else {
    // Only use the last-provided value if an array was returned (ie. when there were redirects involved),
    // and ignore parameters such as "; charset=UTF-8"
    $header_content_type = is_array($headers['content-type']) ? end($headers['content-type']) : $headers['content-type'];
    $header_mime_type = strtolower(trim(explode(';', $header_content_type)[0]));

    // Attempt to handle downloads or other mime-types by passing proxying them through.
    if (!in_array($header_mime_type, $compatible_content_types)) {
        $filesize = 0;
        if (array_key_exists('content-length', $headers)) {
            $filesize = is_array($headers['content-length']) ? end($headers['content-length']) : $headers['content-length'];
        }

        // Check if the linked file isn't too large for us to proxy.
        if ($filesize > $proxy_download_max_filesize) {
            echo 'Failed to proxy file download, it\'s too large. :( <br>';
            echo 'You can try downloading the file directly: ' . $article_url;
            die();
        }
        else {
            $contentType = $header_content_type;

            $filename = basename($url['path']);

            // If no filename can be deduced from the URL, set a placeholder filename
            if (!$filename) {
                $filename = "download";
            }
            
            // Set the content headers based on the file we're proxying through.
            header('Content-Type: ' . $contentType);
            header('Content-Length: ' . $filesize);
            // Set the content-disposition to encourage the browser to download the file.
            header('Content-Disposition: attachment; filename="'. $filename . '"');

            // Use readfile 
            readfile($article_url);
            die();
        }
    }
}

use fivefilters\Readability\Readability;
use fivefilters\Readability\Configuration;
use fivefilters\Readability\ParseException;

$configuration = new Configuration();
$configuration
    ->setArticleByLine(false)
    ->setFixRelativeURLs(true)
    ->setOriginalURL('http://' . $host);

$readability = new Readability($configuration);

//some sites (e.g. Wikipedia) reject requests that have no User-Agent
$context = stream_context_create(array(
    'http' => array('header' => "User-Agent: Mozilla/5.0 (compatible; FrogFind/1.0)\r\n")
));

if(!$article_html = file_get_contents($article_url, false, $context)) {
    $error_text .=  "Failed to get the article :( <br>";
}

try {
    $readability->parse($article_html);
    $readable_article = strip_tags($readability->getContent(), '<a><ol><ul><li><br><p><small><font><b><strong><i><em><blockquote><h1><h2><h3><h4><h5><h6>');
    $readable_article = str_replace( 'strong>', 'b>', $readable_article ); //change <strong> to <b>
    $readable_article = str_replace( 'em>', 'i>', $readable_article ); //change <em> to <i>
    
    $readable_article = clean_str($readable_article);
    $readable_article = str_replace( 'href="http', 'href="/read.php?a=http', $readable_article ); //route links through proxy
    
} catch (ParseException $e) {
    $error_text .= 'Sorry! ' . $e->getMessage() . '<br>';
}

//replace chars that old machines probably can't handle
function clean_str($str) {
    $str = str_replace( "‘", "'", $str );    
    $str = str_replace( "’", "'", $str );  
    $str = str_replace( "“", '"', $str ); 
    $str = str_replace( "”", '"', $str );
    $str = str_replace( "–", '-', $str );

    return $str;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 2.0//EN">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 
 <html>
 <head>
     <title><?php echo $readability->getTitle();?></title>
 </head>
 <body>
    <p>
        <form action="/read.php" method="get">
        <a href="/">Back to <b><font color="#008000">Frog</font><font color="000000">Find!</font></a></b> | Browsing URL: <input type="text" size="38" name="a" value="<?php echo $article_url ?>">
        <input type="submit" value="Go!">
        </form>
    </p>
    <hr>
    <h1><?php echo clean_str($readability->getTitle());?></h1>
    <p> <?php
        $img_num = 0;
        $imgline_html = "View page images:";
        foreach ($readability->getImages() as $image_url):
            //we can only do png and jpg
            if (strpos($image_url, ".jpg") || strpos($image_url, ".jpeg") || strpos($image_url, ".png") === true) {
                $img_num++;
                $imgline_html .= " <a href='image.php?i=" . $image_url . "'>[$img_num]</a> ";
            }
        endforeach;
        if($img_num>0) {
            echo  $imgline_html ;
        }
    ?></small></p>
    <?php if($error_text) { echo "<p><font color='red'>" . $error_text . "</font></p>"; } ?>
    <p><font size="4"><?php echo $readable_article;?></font></p>
 </body>
 </html>