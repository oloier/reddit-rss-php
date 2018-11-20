<?php $v = urldecode($_GET['v']);?>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>Video embed</title>
	<style type="text/css">
		* {margin: 0; padding:0; box-sizing: border-box}
		body {background-color:#262626;}
		main video {
			object-fit: contain;
			width: 100vw;
			height: 100vh;
			position: fixed;
			top: 0;
			left: 0;
		}
	</style>
</head>
<body>
	<main>
		<video src="<?=$v?>" controls muted autoplay loop playsinline></video>
	</main>
</body>
</html>
