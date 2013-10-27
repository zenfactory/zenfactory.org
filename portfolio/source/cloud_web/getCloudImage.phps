<?php

require_once("defines.php");
require_once("cloudLib.php");

# Get image name
$imageName = trim($_REQUEST['img']);
$p = "password";
$h = trim($_REQUEST['h']);
$lh = md5($imageName.$p);
if ($h == $lh)
{
	# Setup cloud interface
	$cloud = new CloudInterface(HOST, LOGIN, SECRET);
	$img = $cloud->getObj("/rest/namespace/tests/images/$imageName");

	# Spit out image
	header('Content-Type: image/jpg');
	echo $img;
}
