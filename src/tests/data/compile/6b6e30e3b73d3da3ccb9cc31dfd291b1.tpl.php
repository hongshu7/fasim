<?php $this->langitems = array (
); ?>
<html>
<head>
<title>Server Error</title>
<style type="text/css">
body {
	background-color: #fff;
	margin: 40px;
	font-family: Lucida Grande, Verdana, Sans-serif;
	font-size: 12px;
	color: #000;
}

#content {
	border: #999 1px solid;
	background-color: #fff;
	padding: 20px 20px 20px 20px;
}

h1 {
	font-weight: normal;
	font-size: 14px;
	color: #990000;
	margin: 0 0 15px 0;
}
</style>
</head>

<body>
	<div id="content">
		<h1><?php echo $code;?> Server Error</h1>
		<div style="color:red;">
			<?php echo $message;?>
		</div>
		<?php if($debug) { ?>
		
		<div style="margin-top:15px;">
			<hr />
			<?php echo $traceString;?>
		</div>
		<?php } ?>
	</div>
</body>
</html>