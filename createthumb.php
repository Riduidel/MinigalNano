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
	error_reporting(0);
/*
if (preg_match("/.jpg$|.jpeg$/i", $previewedFile)) header('Content-type: image/jpeg');
if (preg_match("/.gif$/i", $previewedFile)) header('Content-type: image/gif');
if (preg_match("/.png$/i", $previewedFile)) header('Content-type: image/png');
*/

require 'config.php';
require 'common_functions.php';

// Make sure the "thumbs" directory exists.
if (!is_dir('thumbs')) { mkdir('thumbs',0700); }

// putting that file name in a variable, as we will manipulate it a little
$previewedFile = realpath($_GET['filename']);
// Thumbnail file name and path.
// (We always put thumbnails in jpg for simplification)
$thumb_base_name ="will be filled in now";
if($include_directory_in_thumbnail_name) {
    $thumb_base_name = $previewedFile;
} else {
    $thumb_base_name = basename($previewedFile);
}
$thumbname = 'thumbs/'.sanitize($thumb_base_name).'.jpg';

if (file_exists($thumbname))  // If thumbnail exists, serve it.
{
    $fd = fopen($thumbname, "r");
    $cacheContent = fread($fd,filesize ($thumbname));
    fclose($fd);
    header('Content-type: image/jpeg');
    echo($cacheContent);
}
else // otherwise, generate thumbnail, send it and save it to file.
{

	// Display error image if file isn't found
	if (!is_file($previewedFile)) {
		header('Content-type: image/jpeg');
		$errorimage = ImageCreateFromJPEG('images/questionmark.jpg');
		ImageJPEG($errorimage,null,90);
	}
	
	// Display error image if file exists, but can't be opened
	if (substr(decoct(fileperms($previewedFile)), -1, strlen(fileperms($previewedFile))) < 4 OR substr(decoct(fileperms($previewedFile)), -3,1) < 4) {
		header('Content-type: image/jpeg');
		$errorimage = ImageCreateFromJPEG('images/cannotopen.jpg');
		ImageJPEG($errorimage,null,90);
	}
	
	// Define variables
	$target = "";
	$xoord = 0;
	$yoord = 0;

    if ($_GET['size'] == "") $_GET['size'] = 120; //
       $imgsize = GetImageSize($previewedFile);
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
    if (preg_match("/.jpg$|.jpeg$/i", $previewedFile)) {
		if (function_exists('exif_read_data') && function_exists('imagerotate')) {
			$exif = exif_read_data($previewedFile);
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
			if ($degrees != 0)	$target = imagerotate($target, $degrees, 0);
		}
	}
	
         $target = ImageCreatetruecolor($_GET['size'],$_GET['size']);
         if (preg_match("/.jpg$/i", $previewedFile)) $source = ImageCreateFromJPEG($previewedFile);
         if (preg_match("/.gif$/i", $previewedFile)) $source = ImageCreateFromGIF($previewedFile);
         if (preg_match("/.png$/i", $previewedFile)) $source = ImageCreateFromPNG($previewedFile);
         imagecopyresampled($target,$source,0,0,$xoord,$yoord,$_GET['size'],$_GET['size'],$width,$height);
		 imagedestroy($source);

         //if (preg_match("/.jpg$/i", $previewedFile)) ImageJPEG($target,null,90);
         //if (preg_match("/.gif$/i", $previewedFile)) ImageGIF($target,null,90);
         //if (preg_match("/.png$/i", $previewedFile)) ImageJPEG($target,null,90); // Using ImageJPEG on purpose
         ob_start(); // Start output buffering.
         header('Content-type: image/jpeg'); // We always render the thumbnail in JPEG even if the source is GIF or PNG.
		 ImageJPEG($target,null,80);
         imagedestroy($target);
		 
		 $cachedImage = ob_get_contents(); // Get the buffer content.
         ob_end_flush();// End buffering
         $fd = fopen($thumbname, "w"); // Save buffer to disk
         if ($fd) { fwrite($fd,$cachedImage); fclose($fd); }

}

?>