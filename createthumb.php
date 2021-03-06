<?php
/*
MINIGAL NANO
- A PHP/HTML/CSS based image gallery script

This script and included files are subject to licensing from Creative Commons (http://creativecommons.org/licenses/by-sa/2.5/)
You may use, edit and redistribute this script, as long as you pay tribute to the original author by NOT removing the linkback to www.minigal.dk ("Powered by MiniGal Nano x.x.x")

MiniGal Nano is created by Thomas Rybak

Copyright 2010 by Thomas Rybak
Support: www.minigal.dk
Community: www.minigal.dk/forum

Please enjoy this free script!

Version 0.3.5 modified by Sebastien SAUVAGE (sebsauvage.net):
   - Added thumbnail cache (reduces server CPU load, server bandwith and speeds up client page display).
   - Thumbnails are now always in JPEG even if the source image is PNG or GIF.

USAGE EXAMPLE:
File: createthumb.php
Example: <img src="createthumb.php?filename=photo.jpg&amp;width=100&amp;height=100">
*/
//    error_reporting(E_ALL);
//	error_reporting(0);
/*
if (preg_match("/.jpg$|.jpeg$/i", $previewedFile)) header('Content-type: image/jpeg');
if (preg_match("/.gif$/i", $previewedFile)) header('Content-type: image/gif');
if (preg_match("/.png$/i", $previewedFile)) header('Content-type: image/png');
*/

require 'config.php';
require 'common_functions.php';

/**
 * try to extract a good name from thumb one
 * $inputName is initial image full path
 * $includeDir true or false, true to include path in namr, and siple name in false
 */
function get_thumb_name($inputName, $includeDir) {
	$thumb_base_name ="will be filled in now";
	if($includeDir) {
		$thumb_base_name = $inputName;
	} else {
		$thumb_base_name = basename($inputName);
	}
	return THUMBS . '/'.sanitize($thumb_base_name).'.jpg';
}

function stream_image_file($thumbFile) {
    $fd = fopen($thumbFile, "r");
    $cacheContent = fread($fd,filesize ($thumbFile));
    fclose($fd);
    header('Content-type: image/jpeg');
    echo($cacheContent);
}

function stream_error_image() {
	header('Content-type: image/jpeg');
	$errorimage = ImageCreateFromJPEG('images/questionmark.jpg');
	ImageJPEG($errorimage,null,90);
}

function  create_thumbnail_for_good_image($imageFile, $thumbName, $thumbnailSize) {
	// Define variables
	$target = "";
	$xoord = 0;
	$yoord = 0;

	if ($thumbnailSize == "") $thumbnailSize = 120; //
	$imgsize = GetImageSize($imageFile);
	$width = $imgsize[0];
	$height = $imgsize[1];
	if ($width > $height) { // If the width is greater than the height it’s a horizontal picture
		$xoord = ceil(($width-$height)/2);
		$width = $height;      // Then we read a square frame that  equals the width
	} else {
		$yoord = ceil(($height-$width)/2);
		$height = $width;
	}

	// Rotate JPG pictures
	if (preg_match("/.jpg$|.jpeg$/i", $imageFile)) {
		if (function_exists('exif_read_data') && function_exists('imagerotate')) {
			$exif = exif_read_data($imageFile);
			$ort = $exif['IFD0']['Orientation'];
			$degrees = 0;
			switch($ort)
			{
				case 6: // 90 rotate right
					$degrees = 270;
				break;
				case 8:    // 90 rotate left
					$degrees = 90;
				break;
			}
			if ($degrees != 0)	$target = ImageRotate($target, $degrees, 0);
		}
	}

	 $target = ImageCreateTruecolor($thumbnailSize,$thumbnailSize);
	 if (preg_match("/.jpg$/i", $imageFile)) $source = ImageCreateFromJPEG($imageFile);
	 if (preg_match("/.gif$/i", $imageFile)) $source = ImageCreateFromGIF($imageFile);
	 if (preg_match("/.png$/i", $imageFile)) $source = ImageCreateFromPNG($imageFile);
	 ImageCopyResampled($target,$source,0,0,$xoord,$yoord,$thumbnailSize,$thumbnailSize,$width,$height);
	 imageDestroy($source);

	 //if (preg_match("/.jpg$/i", $imageFile)) ImageJPEG($target,null,90);
	 //if (preg_match("/.gif$/i", $imageFile)) ImageGIF($target,null,90);
	 //if (preg_match("/.png$/i", $imageFile)) ImageJPEG($target,null,90); // Using ImageJPEG on purpose
	 ob_start(); // Start output buffering.
	 header('Content-type: image/jpeg'); // We always render the thumbnail in JPEG even if the source is GIF or PNG.
	 ImageJPEG($target,null,80);
	 imageDestroy($target);
	 
	 $cachedImage = ob_get_contents(); // Get the buffer content.
	 ob_end_flush();// End buffering
	 $fd = fopen($thumbName, "w"); // Save buffer to disk
	 if ($fd) { fwrite($fd,$cachedImage); fclose($fd); }
}

/**
 * Analyze image file, then generate preview for that image by calling create_thumbnail_for_good_image
 * $imageFile the image we want a preview for
 */
function create_thumbnail($imageFile, $thumbFile, $thumbnailSize) {
	// Display error image if file isn't found
	if (!is_file($imageFile)) {
		stream_error_image();
	// Display error image if file exists, but can't be opened
	} elseif (substr(decoct(fileperms($imageFile)), -1, strlen(fileperms($imageFile))) < 4 OR substr(decoct(fileperms($imageFile)), -3,1) < 4) {
		stream_error_image();
	} else {
		create_thumbnail_for_good_image($imageFile, $thumbFile, $thumbnailSize);
	}
}

/**
 * Generate first missing thumbnail below that path
 */
function generateFirstMissingThumbnail($includeDir) {
	return generateFirstMissingThumbnailIn(realpath(PHOTOS), $includeDir);
}

/**
 * Locate in that sub-tree the thumbnail for the first file which has no one generated, and output various paths
 * infos
 * @param directory the directory we search files without thumbnails in
 * @return a boolean containing true if something has been generated, false elsewhen
 */
function generateFirstMissingThumbnailIn($directory, $includeDir) {
	$generatedSomething = false;
    $directoryFiles = getDirectoryList($directory);
    foreach($directoryFiles as $file) {
		if(!$generatedSomething && file_exists($file)) {
			if(is_dir($file)) {
				$generatedSomething = $generatedSomething || generateFirstMissingThumbnailIn($file, $includeDir);
			} else {
				// OK, it's a file so check if it has an associated thumbnail
				$thumbFile = get_thumb_name($file, $includeDir);
				if(file_exists($thumbFile)) {
					// nothing to do as the file already exist
				} else {
					// hey ! that's the file we wanted !
//					echo $file . " will have its thumbnail generated";
					generateIfMissing($file, $includeDir, "");
					$generatedSomething = true;
				}
			}
		}
    }
    return $generatedSomething;
}

function generateIfMissing($previewedFile, $include_directory_in_thumbnail_name, $thumbSize) {
	$thumbname = get_thumb_name($previewedFile, $include_directory_in_thumbnail_name);

	if (file_exists($thumbname)) {
	// If thumbnail exists, serve it.
		stream_image_file($thumbname);
	} else {
	// otherwise, generate thumbnail, send it and save it to file.
		create_thumbnail($previewedFile, $thumbname, $thumbSize);
	}
}

/////////////////////////////////////////////////////////////////////////////////////////
/////////////// this is the real http response script ///////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////
// Make sure the "thumbs" directory exists.
if (!is_dir(THUMBS)) { mkdir(THUMBS,0700); }

if(array_key_exists('filename', $_GET)) {
	// putting that file name in a variable, as we will manipulate it a little
	$previewedFile = realpath($_GET['filename']);

	generateIfMissing($previewedFile, $include_directory_in_thumbnail_name, $_GET['size']);
} elseif(count($_GET)==0) {
	// when there is no thumb given as parameter, just try to find the first file which have no thumb, then generate one thumb for it (and only for that very first file)
	echo generateFirstMissingTHumbnail($include_directory_in_thumbnail_name);
}
?>