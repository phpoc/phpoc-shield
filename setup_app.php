<?php
include_once "config.php";
include_once "/lib/sc_envu.php";
$envu = envu_read("nm0", NM0_ENVU_SIZE, NM0_ENVU_OFFSET);
//serial monitor
if(!($wsm_title = envu_find($envu, "wsm_title")))
	$wsm_title = "Web Serial Monitor";
if(!($wsm_width = envu_find($envu, "wsm_width")))
	$wsm_width = "400";
if(!($wsm_height = envu_find($envu, "wsm_height")))
	$wsm_height = "400";
//remote push
if(!($wrp_title = envu_find($envu, "wrp_title")))
	$wrp_title = "Web Remote Control / Push";
if(!($wrp_width = envu_find($envu, "wrp_width")))
	$wrp_width = "400";
if(!($wrp_but_name = envu_find($envu, "wrp_but_name")))
	$wrp_but_name = "A,B,C,D,E,F,G,H,I";
$wrp_but_name_split = explode(",", $wrp_but_name);
//remote slide
if(!($wrs_title = envu_find($envu, "wrs_title")))
	$wrs_title = "Web Remote Control / Slide";
if(!($wrs_width = envu_find($envu, "wrs_width")))
	$wrs_width = "400";
if(!($wrs_length = envu_find($envu, "wrs_length")))
	$wrs_length = "300";
if(!($wrs_value_min = envu_find($envu, "wrs_value_min")))
	$wrs_value_min = "-100";
if(!($wrs_value_max = envu_find($envu, "wrs_value_max")))
	$wrs_value_max = "100";
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
		table.main{border-collapse:collapse; width:450px;  font-size:10pt;}
		.theader { font-weight: bold;}
		tr { height :28px;}
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
		.buttonSetup {padding: 10px;}
	</style>
	<script type="text/javascript">	
	function add_button()
	{		
		var wrp_but_name = document.forms[0].elements["wrp_but_name[]"];
		var len = wrp_but_name.length;
		
		if (len > 15)
			alert("maximum number of buttons is 15.");
		else
		{
			var addPlace = document.getElementById("buttonSetup");
			var addRow = addPlace.insertRow();
			var addCol1 = addRow.insertCell();
			var addCol2 = addRow.insertCell();
			
			addCol1.innerHTML = "<input type='checkbox' name='chk_delete'>";
			addCol2.innerHTML = "<input type='text' name='wrp_but_name[]' size='10' maxlength='5'>";
		}
	}	
	function delete_button()
	{
		var delPlace = document.getElementById("buttonSetup");
		var chk_delete = document.getElementsByName("chk_delete");		
		var length = chk_delete.length;

		for(var i = length-1; i >= 0; i--)
		{
			if(chk_delete[i].checked)
			{
				if(chk_delete.length > 1)
					delPlace.deleteRow(i);
				else
					chk_delete[i].checked = false;				
			}		
		}		
	}
	function excSubmit()
	{			
		var phpoc_setup = document.phpoc_setup;	
		
		var wrp_but_name = document.forms[0].elements["wrp_but_name[]"];
		var len = wrp_but_name.length;
		var wrp_but_name_all = "";

		if (len === undefined)
		{	
			var but_name = wrp_but_name.value;
		
			if (but_name == "")
			{
				alert("Please check the button name.");	
				wrp_but_name.focus();
				return;
			}
			else
				wrp_but_name_all = wrp_but_name.value + ",";
		}
		else
		{
			for (var i = 0; i < len; i++) 
			{				
				var but_name = wrp_but_name[i].value;
				if (but_name == "")
				{
					alert("Please check the button name.");	
					wrp_but_name[i].focus();
					return;
				}
				else
					wrp_but_name_all += wrp_but_name[i].value + ",";
			}
		}
		phpoc_setup.wrp_but_name_all.value = wrp_but_name_all;
			
		var wsm_width = phpoc_setup.wsm_width.value;
		if(wsm_width == "" || wsm_width <= '0')
		{
			alert("Please check the Serial monitor Width size.");	
			phpoc_setup.wsm_width.focus();
			return;
		}
		
		var wsm_height = phpoc_setup.wsm_height.value;
		if(wsm_height == "" || wsm_height <= '0')
		{
			alert("Please check the Serial monitor Height size.");	
			phpoc_setup.wsm_height.focus();
			return;
		}	
		
		var wrp_width = phpoc_setup.wrp_width.value;
		if(wrp_width == "" || wrp_width <= '0')
		{
			alert("Please check the Remote Push Width size.");	
			phpoc_setup.wrp_width.focus();
			return;
		}
		
		var wrs_width = phpoc_setup.wrs_width.value;
		if(wrs_width == "" || wrs_width <= '0')
		{
			alert("Please check the Remote Slide Width size.");	
			phpoc_setup.wrs_width.focus();
			return;
		}

		var wrs_length = phpoc_setup.wrs_length.value;
		if(wrs_length == "" || wrs_length <= '0')
		{
			alert("Please check the Remote Slide Length size.");	
			phpoc_setup.wrs_length.focus();
			return;
		}
				
		var wrs_value_min = phpoc_setup.wrs_value_min.value;
		var wrs_value_max = phpoc_setup.wrs_value_max.value;
		if(parseInt(wrs_value_max) <= parseInt(wrs_value_min))
		{
			alert("Please check the Remote Slide range.");	
			phpoc_setup.wrs_value_max.focus();
			return;
		}	
		
		phpoc_setup.submit();
	}

	</script>
</head>
<body>
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
						<a href="javascript:excSubmit();">SAVE</a>				
					</div>
				</div>
			</center>
		</div>
		
		<div class="subHeader">
		</div>		
	</div>	
	<br /><br /><br /><br />
	<form name="phpoc_setup" action="setup_app_ok.php" method="post">
	<input type="hidden" name="wrp_but_name_all" value="" size="10">
	<center>	
		<hr style="margin:50px 0 -10px 0; width:450px;" size="6" noshade>
		<h1>Web Serial Monitor</h1>
		
		<table class="main">
			<tr class="zebra">
				<td width="40%" class="theader">Title</td>	
				<td><input type="text" name="wsm_title" value="<? echo $wsm_title?>" size="30"></td>
			</tr>
			<tr>
				<td width="40%" class="theader">Width</td>	
				<td><input type="text" name="wsm_width" value="<? echo $wsm_width?>" size="10"> px</td>
			</tr>
			<tr class="zebra">
				<td class="theader">Height</td>	
				<td><input type="text" name="wsm_height" value="<? echo $wsm_height?>" size="10"> px</td>
			</tr>
		</table>	
		
		<hr style="margin:50px 0 -10px 0; width:450px;" size="6" noshade>
		<h1>Remote Push</h1>
		
		<table class="main">
			<tr class="zebra">
				<td width="40%" class="theader">Title</td>	
				<td><input type="text" name="wrp_title" value="<? echo $wrp_title?>" size="30"></td>
			</tr>
			<tr>
				<td class="theader">Width</td>	
				<td><input type="text" name="wrp_width" value="<? echo $wrp_width?>" size="10"> px</td>
			</tr>
			<tr class="zebra">
				<td class="theader">Button</td>	
				<td class="buttonSetup">
					<center>
						<button type="button" onClick="add_button()">Add</button>
						<button type="button" onClick="delete_button()">Delete</button>
						<table id="buttonSetup">
							<?php
							for ($i=0; $i < count($wrp_but_name_split); $i++)
							{?>							
							<tr>
								<td><input name="chk_delete" type="checkbox"></td>
								<td><input name="wrp_but_name[]" type="text" size="10" maxlength="5" value="<?php echo $wrp_but_name_split[$i]?>"></td>
							</tr>
							<?php}?>
						</table>
					</center>
				</td>
			</tr>
		</table>
		
		<hr style="margin:50px 0 -10px 0; width:450px;" size="6" noshade>
		<h1>Remote Slide</h1>
		
		<table class="main">
			<tr class="zebra">
				<td width="40%" class="theader">Title</td>	
				<td><input type="text" name="wrs_title" value="<? echo $wrs_title?>" size="30"></td>
			</tr>
			<tr>
				<td width="40%" class="theader">Width</td>	
				<td><input type="text" name="wrs_width" value="<? echo $wrs_width?>" size="10"> px</td>
			</tr>
			<tr class="zebra">
				<td class="theader">Length</td>	
				<td><input type="text" name="wrs_length" value="<? echo $wrs_length?>" size="10"></td>
			</tr>
			<tr>
				<td class="theader">Max value</td>	
				<td><input type="text" name="wrs_value_max" value="<? echo $wrs_value_max?>" size="10"></td>
			</tr>
			<tr class="zebra">
				<td class="theader">Min value</td>	
				<td><input type="text" name="wrs_value_min" value="<? echo $wrs_value_min?>" size="10"></td>
			</tr>
		</table>
		
	</center>	
	</form>
	<br /><br /><br /><br />
	<div id="footer">
		<div class="superFooter">
		</div>
	</div>	
</body>
</html>
