<?php

function rtc_get_date()
{
	$pid_rtc = pid_open("/mmap/rtc0");
	$date = pid_ioctl($pid_rtc, "get date");
	pid_close($pid_rtc);
	
	$rtc_time = substr($date,  0, 4) . "-" . substr($date,  4, 2) . "-" . substr($date,  6, 2) . " " . substr($date,  8, 2) . ":" . substr($date,  10, 2) . ":" . substr($date, 12, 2); 
	
	return $rtc_time;
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>PHPoC</title>
	<meta content="initial-scale=0.7, maximum-scale=1.0, minimum-scale=0.5, width=device-width, user-scalable=yes" name="viewport">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /> 
	<style type="text/css">
		body { font-family: verdana, Helvetica, Arial, sans-serif, gulim; height:750px; }
		h1 { font-weight: bold; font-family : verdana, Helvetica, Arial, verdana, sans-serif, gulim; font-size:15pt; padding-bottom:5px;}
		table {border-collapse:collapse; width:450px;  font-size:10pt;}
		.theader { font-weight: bold;}
		tr {height :28px;}
		td { padding-left: 10px; text-align: left;}
		.superHeader {height: 2em; color: white; background-color: rgb(0,153,153); font-size:9pt; position:fixed; left:0; right:0; top:0; z-index:5;  }		
		.right {
		  color: white;
		  position: absolute;
		  right: 1px;
		  bottom: 4px;
		  font-size:9pt;		  
		}	
		.left {
		  color: white;
		  position: absolute;
		  left: 1px;
		  bottom: 4px;
		  font-size:9pt;		  
		}
		.right a, .left a
		{
		  color: white;
		  background-color: transparent;
		  text-decoration: none;
		  margin: 0;
		  padding:0 1ex 0 1ex;
		}			
		.right a:hover, .left a:hover 
		{
		  color: white;
		  text-decoration: underline;
		 }		 
		.midHeader {color: white; background-color: rgb(6, 38, 111);  position:fixed; left:0; right:0; top:1.5em;  z-index:3;}
		.headerTitle {
		  font-size: 250%;
		  font-weight: normal;
		  margin: 0 0 0 4mm;
		  padding: 0.25ex 0 1ex 0;
		  font-family: impact;
		}
		.headerMenu{
			position:relative;
			width: 450px;
			padding: 5px;
		}
		#footer{margin:0 auto; height:auto !important; height:100%; margin-bottom:-100px;  }
		.superFooter {
			height: 2em; color: white; background-color: rgb(6, 38, 111); font-size:9pt; position:fixed; left:0; right:0; bottom:0; z-index:4; 
		}				
		.zebra {background-color : #ECECEC;}
	</style>
	<script type="text/javascript">
	function get_time() 
	{
		var now = new Date();
		
		var year = now.getFullYear();
		var month = now.getMonth() + 1;
		if (month < 10)
			month = "0" + month;
		var day = now.getDate();
		if (day < 10)
			day = "0" + day;
		var h = now.getHours();
		if (h < 10)
			h = "0" + h;
		var m = now.getMinutes();
		if (m < 10)
			m = "0" + m;
		var s = now.getSeconds();
		if (s < 10)
			s = "0" + s;
		
		var host_time = year + '-' + month + '-' + day + " " + h + ':' + m + ':' + s;
		document.getElementById('host_time').innerHTML = host_time;	
		
		return year.toString() + month.toString() + day.toString() + h.toString() + m.toString() + s.toString();
	}
	
	function time_sync()
	{	
		var host_time = get_time();
		
		phpoc_setup.host_time_txt.value = host_time;	
		phpoc_setup.submit();
	}
	</script>
</head>
<body onload="get_time();">
    <div id="header">
		<div class="superHeader">		
			<div class="left">
			</div>	
			<div class="right">
				<a href="http://www.sollae.co.kr" target="_blank">SOLLAE SYSTEMS</a>
			</div>
		</div>

		<div class="midHeader">
			<center>
				<h1 class="headerTitle"><?php echo system("uname -m");?></h1>
				<div class="headerMenu">
					<div class="left">
						<a href="index.php">HOME</a>| 
						<a href="setup_info.php">INFO</a>| 
						<a href="setup_net.php">SETUP</a>| 
						<a href="setup_time.php">TIME</a>| 
						<a href="setup_app.php">APP</a>	
					</div>
					<div class="right">
						<a href="javascript:time_sync();">TIME SYNC.</a>			
					</div>
				</div>
			</center>
		</div>
		
		<div class="subHeader">
		</div>		
	</div>	
	<br /><br /><br /><br />
	<form name="phpoc_setup" action="setup_time_ok.php" method="post">	
	
	<center>	
		<hr style="margin:50px 0 -10px 0; width:450px;" size="6" noshade>
		<h1>Time</h1>
		
		<table>
			<tr class="zebra">
				<td width="40%" class="theader"><?php echo system("uname -m");?> Time</td>	
				<td><?echo rtc_get_date()?></td>
			</tr>
			<tr>
				<td class="theader">Host local Time</td>	
				<td><input type="hidden" name="host_time_txt"><div id="host_time"></div></td>
			</tr>
		</table>	
		<br /><br /><br /><br />
		<strong>Note</strong> : Depending on the network environment, <br />the synchronization may not match with host local time.
	</center>	
	</form>	
	<div id="footer">
		<div class="superFooter">
		</div>
	</div>	
</body>
</html>
