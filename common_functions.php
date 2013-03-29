<?php
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

function sanitize($name)
{
    // Sanitize image filename (taken from http://iamcam.wordpress.com/2007/03/20/clean-file-names-using-php-preg_replace/ )
	$fname=$name;
	$replace="_";
	$pattern="/([[:alnum:]_\.-]*)/";
	$fname=str_replace(str_split_php4(preg_replace($pattern,$replace,$fname)),$replace,$fname);
	return $fname;
}
?>