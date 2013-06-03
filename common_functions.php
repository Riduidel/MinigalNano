<pre>
<?php
define("SANITIZE_TEST", "test_for_sanitize");
define("REMOVE_ACCENTS_TEST", "test_for_remove_accents");
define("PARSE_METADATAS_TEST", "test_for_parse_metadatas");

define("THUMBS", "thumbs");
define("PHOTOS", "photos");


/**
 * Reading directory list, courtesy of http://www.laughing-buddha.net/php/dirlist/
 * @param directory the directory we want to list files of
 * @return a simple array containing the list of absolute file paths. Notice that current file (".") and parent one("..")
 * are not listed here
 */
function getDirectoryList ($directory)  {
    $realPath = realpath($directory);
    // create an array to hold directory list
    $results = array();
    // create a handler for the directory
    $handler = opendir($directory);
    // open directory and walk through the filenames
    while ($file = readdir($handler)) {
        // if file isn't this directory or its parent, add it to the results
        if ($file != "." && $file != "..") {
			$results[] = realpath($realPath . "/" . $file);
        }
    }
    // tidy up: close the handler
    closedir($handler);
    // done!
    return $results;
}

function str_split_php4( $text, $split = 1 ) {
    // place each character of the string into and array
    $array = array();
    for ( $i=0; $i < strlen( $text ); ){
        $key = NULL;
        for ( $j = 0; $j < $split; $j++, $i++ ) {
            $key .= $text[$i];
        }
        array_push( $array, $key );
    }
    return $array;
}

function remove_accents($str)
{
    $from = array(
        "á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï",
        "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç", "Á", "À", "Â",
        "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô",
        "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç"
    );
    $to = array(
        "a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i",
        "o", "o", "o", "o", "o", "u", "u", "u", "u", "c", "A", "A", "A",
        "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O",
        "O", "O", "U", "U", "U", "U", "C"
    );
    return str_replace($from, $to, $str);
}

/**
 * If file is an image, load the parsable image metadatas into return value
 * @param file the file to load metadatas from
 * @return image metadatas if possible, false elsewhere
 */
function parseMetadatasFor($file) {
    $sizeArray = getImageSize($file, $metadatas);
    // yeah instead of simply returning an image size, this method return an array of 7 value, some of which themselves
    // being arrays. And specifically, the element 2 contains the image type
    $imageType = $sizeArray[2];
    if(in_array($imageType , array(IMAGETYPE_GIF , IMAGETYPE_JPEG ,IMAGETYPE_PNG , IMAGETYPE_BMP))) {
        if (isset($metadatas["APP13"])) {
            $iptc = iptcParse($metadatas["APP13"]);
            return $iptc;
        }
    }
    return false;
}

function sanitize($name)
{
    // Sanitize image filename (taken from http://iamcam.wordpress.com/2007/03/20/clean-file-names-using-php-preg_replace/ )
	$source = remove_accents($name);
	$replace = "_";
	$pattern = "/([[:alnum:]_\.-]*)/";
	return str_replace(str_split_php4(preg_replace($pattern,$replace,$source)),$replace,$source);
}

if(array_key_exists(SANITIZE_TEST, $_GET)) {
    echo "sanitize return \"" . sanitize($_GET[SANITIZE_TEST]) . "\"";
}

if(array_key_exists(REMOVE_ACCENTS_TEST, $_GET)) {
    echo "remove accents return \"" . remove_accents($_GET[REMOVE_ACCENTS_TEST]) . "\"";
}

if(array_key_exists(PARSE_METADATAS_TEST, $_GET)) {
	$filename = $_GET[PARSE_METADATAS_TEST];
	echo $filename . "\n";
    echo "test for parseMetadata return \"" . print_r(parseMetadatasFor($filename), true) . "\"";
}
?>
</pre>