<?php
include_once "config.php";
include_once "/lib/sc_envu.php";
$envu = envu_read("nm0", NM0_ENVU_SIZE, NM0_ENVU_OFFSET);

//remote push
if(!($wrp_title = envu_find($envu, "wrp_title")))
	$wrp_title = "Web Remote Control / Push";
if(!($wrp_width = envu_find($envu, "wrp_width")))
	$wrp_width = "400";
if(!($wrp_but_name = envu_find($envu, "wrp_but_name")))
	$wrp_but_name = "A,B,C,D,E,F,G,H,I";
$push_text = explode(",", $wrp_but_name);
$push_text_len = count($push_text);

//serial monitor
if(!($wsm_title = envu_find($envu, "wsm_title")))
	$wsm_title = "Web Serial Monitor";
if(!($wsm_width = envu_find($envu, "wsm_width")))
	$wsm_width = "400";
if(!($wsm_height = envu_find($envu, "wsm_height")))
	$wsm_height = "400";
if(!($baud = envu_find($envu, "wsm_baud")))
 $baud = "9600";
?>
<!DOCTYPE html>
<html>
<head>
<title>PHPoC Shield - Web Remote Control for Arduino</title>
<meta name="viewport" content="width=device-width, initial-scale=0.7, maximum-scale=0.7">
<style>
body { font-family: verdana, Helvetica, Arial, sans-serif, gulim; text-align: center; }
h1 { font-weight: bold; font-size: 25pt; }
h2 { font-weight: bold; font-size: 15pt; }
button { font-weight: bold; font-size: 15pt; } 
#remote { margin:0 auto; width: <?echo $wrp_width?>px; }
.circle_button { 
		display: inline-block; width: 110px; height: 110px; 
		border-radius: 50%; font-size: 20px; color: white; line-height: 110px;
		text-align: center; font-weight: bold; background: #eee; margin: 7px;
}
textarea { width:<?echo$wsm_width?>px; height:<?echo$wsm_height?>px; padding:10px; font-family:courier; font-size:14px; }
</style>
<script>
var push_info = [];
var wrp_but_name = "<?php echo $wrp_but_name;?>";
var push_text = wrp_but_name.split(",");
var push_length = push_text.length;
var push_font = "20px Arial";
var ws;
var ws_monitor;
var wsm_max_len = 4096; /* bigger length causes uart0 buffer overflow with low speed smart device */

function connect_monitor()
{
	if(ws_monitor == null)
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
		ws_monitor = new WebSocket("ws://" + ws_host_addr + "/serial_monitor", "uint8.phpoc");

		document.getElementById("ws_monitor_state").innerHTML = "CONNECTING";

		ws_monitor.onopen = ws_monitor_onopen;
		ws_monitor.onclose = ws_monitor_onclose;
		ws_monitor.onmessage = ws_monitor_onmessage;
		ws_monitor.binaryType = "arraybuffer";
	}
	else
		ws_monitor.close();
}
function ws_monitor_onopen()
{
	var wsm_baud = document.getElementById("wsm_baud");

	document.getElementById("ws_monitor_state").innerHTML = "<font color='blue'>CONNECTED</font>";
	document.getElementById("bt_monitor_connect").innerHTML = "Disconnect";

	ws_monitor.send("wsm_baud=" + wsm_baud.value + "\r\n");

	wsm_baud.disabled = "true";
}
function ws_monitor_onclose()
{
	var wsm_baud = document.getElementById("wsm_baud");

	document.getElementById("ws_monitor_state").innerHTML = "<font color='gray'>CLOSED</font>";
	document.getElementById("bt_monitor_connect").innerHTML = "Connect";

	ws_monitor.onopen = null;
	ws_monitor.onclose = null;
	ws_monitor.onmessage = null;
	ws_monitor = null;

	wsm_baud.disabled = "";
}
function ws_monitor_onmessage(e_msg)
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

function init()
{	
	if(ws == null)
	{
		ws = new WebSocket("ws://<?echo _SERVER("HTTP_HOST")?>/remote_push", "text.phpoc");
		document.getElementById("ws_state").innerHTML = "CONNECTING";
		
		ws.onopen = ws_onopen;
		ws.onclose = ws_onclose;
		ws.onmessage = ws_onmessage; 
	}
	else
		ws.close();
	
	for(var push_id = 0; push_id < push_length; push_id++)
	{
		push_info[push_id] = {state:false, identifier:null, font:push_font, text:push_text[push_id]};
		update_push(push_id, false);
	}

	var remote = document.getElementById("remote");
	
	remote.ontouchstart = mouse_down;
	remote.ontouchend = mouse_up;
	remote.ontouchcancel = mouse_up;
	remote.ontouchout = mouse_move;
	remote.onmousedown = mouse_down;
	remote.onmouseup = mouse_up;
	remote.onmouseout = mouse_up; 
	remote.onmousemove = mouse_move; 
	
	//--------------------------------
	

	
	if(ws_monitor == null)
	{
		ws_monitor = new WebSocket("ws://<?echo _SERVER("HTTP_HOST")?>/serial_monitor", "uint8.phpoc");
		document.getElementById("ws_monitor_state").innerHTML = "CONNECTING";

		ws_monitor.onopen = ws_monitor_onopen;
		ws_monitor.onclose = ws_monitor_onclose;
		ws_monitor.onmessage = ws_monitor_onmessage;
		ws_monitor.binaryType = "arraybuffer";
	}
	else
		ws_monitor.close();
	

}
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
		ws = new WebSocket("ws://" + ws_host_addr + "/remote_push", "text.phpoc");

		document.getElementById("ws_state").innerHTML = "CONNECTING";

		ws.onopen = ws_onopen;
		ws.onclose = ws_onclose;
		ws.onmessage = ws_onmessage;
	}
	else
		ws.close();
}
function ws_onopen()
{
	document.getElementById("ws_state").innerHTML = "<font color='blue'>CONNECTED</font>";
	document.getElementById("bt_connect").innerHTML = "Disconnect";

	for(var push_id = 0; push_id < push_length; push_id++)
		update_push(push_id, false);
}
function ws_onclose()
{
	document.getElementById("ws_state").innerHTML = "<font color='gray'>CLOSED</font>";
	document.getElementById("bt_connect").innerHTML = "Connect";

	ws.onopen = null;
	ws.onclose = null;
	ws.onmessage = null;
	ws = null;

	for(var push_id = 0; push_id < push_length; push_id++)
		update_push(push_id, false);
}
function ws_onmessage(e_msg)
{
	e_msg = e_msg || window.event; // MessageEvent

	alert("msg : " + e_msg.data);
}
function update_push(push_id, state)
{
	var button = document.getElementById(push_id);
	var push = push_info[push_id];

	if(ws && (ws.readyState == 1))
	{
		button.style.border = "1px solid blue";
		if(state)
			button.style.backgroundColor = "blue";
		else
			button.style.backgroundColor = "skyblue";
	}
	else
	{
		button.style.border = "1px solid gray";
		if(state)
			button.style.backgroundColor = "gray";
		else
			button.style.backgroundColor = "silver";
	}

	push.state = state;

	if(!state)
		push.identifier = null;

	if(ws && (ws.readyState == 1))
	{
		if(state)
			ws.send(String.fromCharCode(0x41 + Number(push_id))); // 'A' ~ 'I'
		else
			ws.send(String.fromCharCode(0x61 + Number(push_id))); // 'a' ~ 'i'
	}
	
}
function mouse_down(event)
{
	var debug = document.getElementById("debug");

	//debug.innerHTML = "";
	var push_id = event.target.id;	
	
	if(event.changedTouches)
	{
		for(var touch_id = 0; touch_id < event.changedTouches.length; touch_id++)
		{
			var touch = event.changedTouches[touch_id];

			if(push_id < push_length)
			{
				var push = push_info[push_id];

				if(push.state == false)
				{
					update_push(push_id, true);
					push.identifier = touch.identifier;

					//debug.innerHTML += ("+" + push_id + "/" + touch.identifier + " ");
				}

				//debug.innerHTML += (push_id + " ");
			}
		}
	}
	else
	{
		if(push_id < push_length)
		{
			update_push(push_id, true);
			//debug.innerHTML += (push_id + " ");
		}
	}

	event.preventDefault();
}
function mouse_up(event)
{
	var debug = document.getElementById("debug");
	var push_id;

	//debug.innerHTML = "";

	if(event.changedTouches)
	{
		for(var touch_id = 0; touch_id < event.changedTouches.length; touch_id++)
		{
			var touch = event.changedTouches[touch_id];

			for(var push_id = 0; push_id < push_length; push_id++)
			{
				if(touch.identifier == push_info[push_id].identifier)
					break;
			}

			if(push_id < push_length)
			{
				update_push(push_id, false);
				//debug.innerHTML += ("-" + push_id + "/" + touch.identifier + " ");
			}
		}
	}
	else
	{
		for(var push_id = 0; push_id < push_length; push_id++)
		{
			if(push_info[push_id].state)
			{
				update_push(push_id, false);
				break;
			}
		}
	}

	event.preventDefault();
}
function mouse_move(event)
{
	event.preventDefault();
}
window.onload = init;
</script>
</head>

<body>

<h1><?php echo $wrp_title?></h1>
<br /><br />
<textarea id="wsm_text" readonly="readonly"></textarea><br>

<h2>Serial Monitor WebSocket <font id="ws_monitor_state" color="gray">CLOSED</font></h2>
<button id="bt_monitor_connect" type="button" onclick="connect_monitor();">Connect</button>
<button id="bt_monitor_clear" type="button" onclick="wsm_clear();">Clear</button>
<select id="wsm_baud">
	<option value = "9600" <?if($baud=="9600")echo"selected"?> >9600</option>
	<option value = "19200" <?if($baud=="19200")echo"selected"?> >19200</option>
	<option value = "38400" <?if($baud=="38400")echo"selected"?> >38400</option>
	<option value = "57600" <?if($baud=="57600")echo"selected"?> >57600</option>
	<option value = "115200" <?if($baud=="115200")echo"selected"?> >115200</option>
</select>
<br /><br />
<br /><br />
<div id="remote">
<?php
	for ($i=0; $i<$push_text_len; $i++)
	{
		echo "<div class='circle_button' id='" . (string) $i . "'>" . $push_text[$i] . "</div>";
	}
?>
</div>
<br /><br />
<h2>Remote Push WebSocket <font id="ws_state" color="gray">CLOSED</font></h2>
<button id="bt_connect" type="button" onclick="connect_onclick();">Connect</button>
<span id="debug"></span>

</body>
</html>
