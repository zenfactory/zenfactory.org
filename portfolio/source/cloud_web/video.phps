<html>
<head>
	<title>PEER1 Cloud Serving a Video</title>
</head>
<body>
	<h1>Cloud video example</h1>
	<p>These are videos that is stored on the cloud, they are never saved to the webserver, the webserver is just used to interface.</p>
	<?php
		$p = "password";
		$h = md5("shasta.ogg".$p);
		echo "<video src='getCloudVideo.php?video=shasta.ogg&h={$h}' controls='controls'>your browser does not support the video tag</video><br /><br /><hr />";
		$h = md5("bunny.ogg".$p);
		echo "<video src='getCloudVideo.php?video=bunny.ogg&h={$h}' controls='controls'>your browser does not support the video tag</video>";
	?>
</body>
</html>
