<?php
include_once "config.php";
include_once "/lib/sc_envu.php";
$envu = envu_read("nm0", NM0_ENVU_SIZE, NM0_ENVU_OFFSET);
if(!($wsm_width = envu_find($envu, "wsm_width")))
	$wsm_width = "400";
if(!($wsm_height = envu_find($envu, "wsm_height")))
	$wsm_height = "400";
if(!($baud = envu_find($envu, "wsm_baud")))
 $baud = "9600";
?>
<html>
<head>
<title>PHPoC Shield - Web Serial Monitor for Arduino</title>
<meta name="viewport" content="width=device-width, initial-scale=0.7">
<style>
body { text-align:center; }
h1 { font-weight: bold; font-size: 25pt; }
h2 { font-weight: bold; font-size: 15pt; }
button { font-weight: bold; font-size: 15pt; }
select { font-weight: bold; font-size: 15pt; }
textarea { width:<?echo$wsm_width?>px; height:<?echo$wsm_height?>px; padding:10px; font-family:courier; font-size:14px; }
 </style>
<script>
var ws;
var wsm_max_len = 4096; /* bigger length causes uart0 buffer overflow with low speed smart device */
function connect_onclick()
{
	if(ws == null)
	{
		var ws_host_addr = "<?echo _SERVER("HTTP_HOST")?>";
		var debug = document.getElementById("debug");

		if((navigator.platform.indexOf("Win") != -1) && (ws_host_addr.charAt(0) == "["))
		{
			// network resource identifier to UNC path name conversion
			ws_host_addr = ws_host_addr.replace(/[\[\]]/g, '');
			ws_host_addr = ws_host_addr.replace(/:/g, "-");
			ws_host_addr += ".ipv6-literal.net";
		}

		//debug.innerHTML = "<br>" + navigator.platform + " " + ws_host_addr;
		ws = new WebSocket("ws://" + ws_host_addr + "/serial_monitor", "uint8.phpoc");

		document.getElementById("ws_state").innerHTML = "CONNECTING";

		ws.onopen = ws_onopen;
		ws.onclose = ws_onclose;
		ws.onmessage = ws_onmessage;
		ws.binaryType = "arraybuffer";
	}
	else
		ws.close();
}
function ws_onopen()
{
	var wsm_baud = document.getElementById("wsm_baud");

	document.getElementById("ws_state").innerHTML = "<font color='blue'>CONNECTED</font>";
	document.getElementById("bt_connect").innerHTML = "Disconnect";

	ws.send("wsm_baud=" + wsm_baud.value + "\r\n");

	wsm_baud.disabled = "true";
}
function ws_onclose()
{
	var wsm_baud = document.getElementById("wsm_baud");

	document.getElementById("ws_state").innerHTML = "<font color='gray'>CLOSED</font>";
	document.getElementById("bt_connect").innerHTML = "Connect";

	ws.onopen = null;
	ws.onclose = null;
	ws.onmessage = null;
	ws = null;

	wsm_baud.disabled = "";
}
function ws_onmessage(e_msg)
{
	e_msg = e_msg || window.event; // MessageEvent

	var wsm_text = document.getElementById("wsm_text");
	var len = wsm_text.value.length;
	var u8view = new Uint8Array(e_msg.data);

	if(len > (wsm_max_len + wsm_max_len / 10))
		wsm_text.innerHTML = wsm_text.value.substring(wsm_max_len / 10);

	//for(i = 0; i < u8view.length; i++)
	//	wsm_text.innerHTML += String.fromCharCode(u8view[i]);

	wsm_text.innerHTML += String.fromCharCode.apply(null, u8view);

	wsm_text.scrollTop = wsm_text.scrollHeight;
}
function wsm_clear()
{
	document.getElementById("wsm_text").innerHTML = "";
}
</script>
</head>
<body>

<p>
<h1>Web Serial Monitor</h1>
</p>

<textarea id="wsm_text" readonly="readonly"></textarea><br>

<h2>WebSocket <font id="ws_state" color="gray">CLOSED</font></h2>
<button id="bt_connect" type="button" onclick="connect_onclick();">Connect</button>
<button id="bt_clear" type="button" onclick="wsm_clear();">Clear</button>
<select id="wsm_baud">
	<option value = "9600" <?if($baud=="9600")echo"selected"?> >9600</option>
	<option value = "19200" <?if($baud=="19200")echo"selected"?> >19200</option>
	<option value = "38400" <?if($baud=="38400")echo"selected"?> >38400</option>
	<option value = "57600" <?if($baud=="57600")echo"selected"?> >57600</option>
	<option value = "115200" <?if($baud=="115200")echo"selected"?> >115200</option>
</select>
<span id="debug"></span>

</body>
</html>

