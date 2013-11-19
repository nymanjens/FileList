<?php
/**
 * Download file
 * 
 * Author: Jens Nyman <nymanjens.nj@gmail.com>
 * 
 */

$IP = dirname(__FILE__) . '/../../';
putenv( 'MW_INSTALL_PATH='. $IP);
require_once( $IP . 'includes/WebStart.php' );

if($wgRequest->getVal('name') == '')
	die("Error: no name found");
if($wgRequest->getVal('file') == '')
	die("Error: no file found");
$filename = $wgRequest->getVal('file');
$name = $wgRequest->getVal('name');
$ext = fl_file_get_extension($filename);

// get url and path
$image = wfFindFile($filename);
$path = $image->getLocalRefPath();
$url = $image->getUrl();

if(!file_exists($path))
	die("Error: This file could not be found");

if(in_array($ext, $fileListOpenInBrowser)) {
	header("Location: $url");
} else {
	header("Content-type: application/$ext");
	header("Content-Disposition: attachment; filename=\"$name\"");
	readfile($path);
}



