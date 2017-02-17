<?php
include_once "config.php";
include_once "/lib/sc_envu.php";
$envu = envu_read("nm0", NM0_ENVU_SIZE, NM0_ENVU_OFFSET);
if(!($wrp_title = envu_find($envu, "wrp_title")))
	$wrp_title = "Web Remote Control / Push";
if(!($wrp_width = envu_find($envu, "wrp_width")))
	$wrp_width = "400";
if(!($wrp_but_name = envu_find($envu, "wrp_but_name")))
	$wrp_but_name = "A,B,C,D,E,F,G,H,I";
$push_text = explode(",", $wrp_but_name);
$push_text_len = count($push_text);
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
</style>
<script>
var push_info = [];
var wrp_but_name = "<?php echo $wrp_but_name;?>";
var push_text = wrp_but_name.split(",");
var push_length = push_text.length;
var push_font = "20px Arial";
var ws;

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
<div id="remote">
<?php
	for ($i=0; $i<$push_text_len; $i++)
	{
		echo "<div class='circle_button' id='" . (string) $i . "'>" . $push_text[$i] . "</div>";
	}
?>
</div>
<br /><br />
<h2>WebSocket <font id="ws_state" color="gray">CLOSED</font></h2>
<button id="bt_connect" type="button" onclick="connect_onclick();">Connect</button>
<span id="debug"></span>

</body>
</html>
