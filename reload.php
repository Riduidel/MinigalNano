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

require 'common_functions.php';


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

/**
 * If file is an image, load the parsable image metadatas into return value
 * @param file the file to load metadatas from
 * @return image metadatas if possible, false elsewhere
 */
function parseMetadatasFor($file) {
    $sizeArray = getimagesize($file, $metadatas);
    // yeah instead of simply returning an image size, this method return an array of 7 value, some of which themselves
    // being arrays. And specifically, the element 2 contains the image type
    $imageType = $sizeArray[2];
    if(in_array($imageType , array(IMAGETYPE_GIF , IMAGETYPE_JPEG ,IMAGETYPE_PNG , IMAGETYPE_BMP))) {
        if (isset($metadatas["APP13"])) {
            $iptc = iptcparse($metadatas["APP13"]);
            return $iptc;
        }
    }
    return false;
}

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
            }
        }
    }
    return $returned;
}

function transformMetadata($metadataElement, $metadataArray) {
    // calls sanitize function on each array element
    $sanitizedArray = array();
    foreach($metadataArray as $m) {
        $sanitizedArray[] = sanitize($m);
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
        $usedMetadata = transformMetadata($metadataElement, $metadata[$metadataElement]);
        $returned = array_merge($returned, $usedMetadata);
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
    $targetFolder = "photos/" . implode("/", $foldersArray);
    $filename = sanitize(basename($imageFile));
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
            <?= $file ?> => <?= generatePathsFor($file, $metadata, $requiredPaths); ?>
            </li>
        <?php } ?>
        </ul>
    </body>
</html>