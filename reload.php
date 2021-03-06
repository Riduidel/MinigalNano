<?php 

/* A list of IPTC entries, discovered by reading them */
define("EVENT_IPTC_KEY", "2#022");
define("DATE_IPTC_KEY", "2#055");
define("AUTHOR_IPTC_KEY", "2#080");

define("TAGS_IPTC_KEY", "2#025");
define("PERSONS_IPTC_KEY", "2#118");

define("COUNTRY_IPTC_KEY", "2#101");
define("STATE_IPTC_KEY", "2#095");
define("TOWN_IPTC_KEY", "2#090");
define("STREET_IPTC_KEY", "2#092");

define("COMMENTS_IPTC_KEY", "2#227");

define("ENCODING", "UTF-8");

require 'common_functions.php';

header('Content-Type: text/html; charset=utf-8'); // We use UTF-8 for proper international characters handling.

/**
 * Build a hash linking source image paths to hash containing various image metadatas (detailled later)
 * @param directory directory in which images and metadatas will be recursively searched
 * @return a hash linking imges absolute paths to their metadatas
 */
function getImagesMetadatas($directory) {
    $returned = array();
    $directoryFiles = getDirectoryList($directory);
    foreach($directoryFiles as $file) {
        if(is_dir($file)) {
            $returned = array_merge($returned, getImagesMetadatas($file));
        } else {
            $metadatas = parseMetadatasFor($file);
            if($metadatas) {
                $returned[$file] = $metadatas;
				// Generate a "nice" log message
//				$message = basename($file);
//				$message .= "\n" . print_r($metadatas, true);
//				error_log($message);
            }
        }
    }
    return $returned;
}

function transformMetadata($metadataElement, $metadataArray) {
    // calls sanitize function on each array element
    $sanitizedArray = array();
    foreach($metadataArray as $m) {
        $sanitizedArray[] = sanitize(mb_convert_encoding($m, ENCODING));
    }
//    array_walk($metadataArray, 'sanitizeArrayElement');
    switch($metadataElement) {
        case DATE_IPTC_KEY:
            return array(substr($sanitizedArray[0], 0, 4));
    }
    return $sanitizedArray;
}

/**
 * Create an image path (well, a list of image paths) from the given path transformation
 * @param file the source file for which output paths will be created
 * @param metadata the file associated IPTC metadatas
 * @param pathTransformation the transformation to apply, expressed as an array of IPTC keys
 * @return an array describing all the paths under which the source file will be available. 
 * Each element of this array will be itself will be an array in which all elements are folders.
 */
function createImagePathFor($file, $metadata, $pathTransformation) {
    $returned = array();
    foreach($pathTransformation as $metadataElement) {
        // This is an array containing elements of that particular metadataElement
		if(array_key_exists($metadataElement, $metadata)) {
			$usedMetadata = transformMetadata($metadataElement, $metadata[$metadataElement]);
			$returned = array_merge($returned, $usedMetadata);
		}
    }
    // current path is only a part of the solution
    return $returned;
}

/**
 * Create a symlink from image file to the folder at the end of that folder array
 * @param imageFile source file to link
 * @param foldersArray an array of folders that will be joined below the photos one
 */
function linkImageInto($imageFile, $foldersArray) {
    $targetFolder = PHOTOS . "/" . implode("/", $foldersArray);
    $filename = sanitize(mb_convert_encoding(basename($imageFile), ENCODING));
    if(!file_exists($targetFolder)) {
        mkdir($targetFolder, 0777 /* default total access right set */, true /* recursively create ! */);
    }
    $targetPath = $targetFolder . "/" . $filename;
    if(file_exists($targetPath)) {
        return "file already exists in " . $targetPath;
    } else {
        if(symlink($imageFile, $targetPath)) {
            return $targetPath;
        } else {
            return "unable to symlink into " . $targetPath;
        }
    }
}


/**
 * Generate all paths by which the given source image will be available by creating symlonks at well-known locations
 * @param file the source from which links are to be generated
 * @param metadata image metadatas for which the paths will be generated
 * @param usedPaths the array of arrays of path transformations : each element in that first array describe a path under 
 * which this image is to be available
 * @return a small html fragment describing in a consise way what have been done on that very image
 */
function generatePathsFor($file, $metadata, $usedPaths) {
    $returned = "<ul>";
//	error_log("file " . basename($file) . " encoded as " . mb_detect_encoding(basename($file)));
    foreach($usedPaths as $baseFolder => $pathTransformation) {
        $imagePath = createImagePathFor($file, $metadata, $pathTransformation);
        // now create required folders
        $foldersArray = array_merge(array($baseFolder), $imagePath);
        $createdLink = linkImageInto($file, $foldersArray);
        $returned = $returned . "<li>" . "<a href=\"index.php?" . implode("/", $imagePath) . "\">" . $createdLink . "</a></li>";
    }
    $returned = $returned . "</ul>";
    return $returned; 
}

/**
 * This is the array containing the various paths that will be built out of the source images
 */
$requiredPaths = array(
    "dates" => array(DATE_IPTC_KEY, EVENT_IPTC_KEY),
    "evenements" => array(EVENT_IPTC_KEY, DATE_IPTC_KEY)
    );
    
    
/*
 * This is the path from which we will read all images
 */
$startPath = "source/2013";


?>
<html>
    <body>
        <h1>Reloading from ? <?= realpath($startPath) ?></h1>
        Damn, it work! 
        <ul>
        <?php 
        $imageMetadatas = getImagesMetadatas($startPath);
        foreach($imageMetadatas as $file => $metadata) { ?>
            <li>
            <?= mb_convert_encoding($file, ENCODING) ?> => <?= generatePathsFor($file, $metadata, $requiredPaths); ?>
            </li>
        <?php } ?>
        </ul>
    </body>
</html>