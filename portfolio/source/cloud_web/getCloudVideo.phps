<?php

require_once("defines.php");
require_once("cloudLib.php");

# Get image name
$p = "password";
$videoName = trim($_REQUEST['video']);
$h = trim($_REQUEST['h']);
$lh = md5($videoName.$p);

if ($h == $lh)
{
	# Spit out image
	header('Content-Type: video/ogg');

	# Setup cloud interface
	$cloud = new CloudInterface(HOST, LOGIN, SECRET);
	$cloud->dumpObj("/rest/namespace/tests/videos/$videoName");
}
