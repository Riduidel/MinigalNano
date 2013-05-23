<?php
define("SANITIZE_TEST", "test_for_sanitize");
define("REMOVE_ACCENTS_TEST", "test_for_remove_accents");

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
?>