<?php

require_once("cloudLib.php");

# Get image
$imageName = trim($_REQUEST['img']);
$h = trim($_REQUEST['h']);
$p = "password";
$lh = md5($imageName.$p);

if ($h == $lh)
{
	$url = "http://www.zenfactory.org/cloud/gallery/getCloudImage.php?img={$imageName}&h={$h}";
	$img = imagecreatefromjpeg($url);

	# Resize
	$thumb_width = "100";
	$thumb_height= "100";
	$original_width = ImageSX($img);
	$original_height = ImageSY($img); 
	$thumbnail = ImageCreate($thumb_width, $thumb_height);
	ImageCopyResized ($thumbnail, $img, 0, 0, 0, 0, $thumb_width, $thumb_height, $original_width, $original_height);

	# Spit out image
	header('Content-Type: image/jpg');
	imagejpeg($thumbnail);
	imagedestroy($img);
	imagedestroy($thumbnail);
}
