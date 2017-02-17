<html>
<head>
<title>PHPoC Shield for Arduino</title>
<meta name="viewport" content="width=device-width, initial-scale=0.7">
<style>
body { text-align:center; }
a { font-size: 20pt; }
a:link { text-decoration: none;}
a:hover { text-decoration: underline;}
 </style>
</head>
<body>

<br><br>
<a href="setup_info.php">Setup</a>

<?if((int)ini_get("init_bcfg")){?>

<br><br><br>
<font color="red">
PHPoC Shield is running in SETUP mode.<br>
Web service is not available except SETUP.
</font>

<?}else{?>

<br><br><br>
<a href="serial_monitor.php">Web Serial Monitor</a>

<br><br><br>
<a href="remote_push.php">Web Remote Control / Push</a>

<br><br><br>
<a href="remote_slide.php">Web Remote Control / Slide</a>

<?}?>

</body>
</html>

