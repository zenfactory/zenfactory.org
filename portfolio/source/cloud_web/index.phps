<?php

require_once("defines.php");
require_once("cloudLib.php");

$cloud = new CloudInterface(HOST, LOGIN, SECRET);
$images = $cloud->ls("/rest/namespace/tests/images");

# Start HTML output
echo "<!DOCTYPE HTML>";
?>
<html>
	<head>
		<title>Peer1 Hosting Cloud Storage Image gallery Demo</title>
        <link rel="stylesheet" href="css/lightbox.css" type="text/css" media="screen" />
        <script src="js/prototype.js" type="text/javascript"></script>
        <script src="js/scriptaculous.js?load=effects,builder" type="text/javascript"></script>
        <script src="js/lightbox.js" type="text/javascript"></script>
		<style type="text/css">
			body 
			{
				background-color: black;
				width: 99%;
				color: white;
				font: sans-serif;
				text-align: center;
			}
			body a
			{
				border: none;
				outline: none;
			}
			body img
			{
				border: none;
				outline: none;
			}
			#container
			{
				margin: auto;
				width: 800px;
				height: 500px;
				border: 8px solid white;
				text-align: center;
				overflow: auto;
			}
			#container img
			{
				border: 1px solid white;
				margin: 20px;
			}
		</style>
	</head>
	<body>
		<h1>PEER1 Hosting Cloud Storage Image Gallery Demo</h1>
		<div id="container">
		<?php
		# Display images
		$i = 0;
		foreach ($images as $img)
		{
		$p = "password";
		$h = md5($img.$p);
        	echo "<a href='getCloudImage.php?img={$img}&h={$h}' rel='lightbox'><img alt='' src='getCloudImageThumb.php?img={$img}&h={$h}' /></a>";
			$i++;
			if ($i > 3)
			{
				echo "<br />";
				$i = 0;
			}
		}
		?>
		</div>
	</body>
</html>			
