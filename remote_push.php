<?php
include_once "config.php";
include_once "/lib/sc_envu.php";
$envu = envu_read("nm0", NM0_ENVU_SIZE, NM0_ENVU_OFFSET);
if(!($wrp_width = envu_find($envu, "wrp_width")))
	$wrp_width = "400";
?>
<!DOCTYPE html>
<html>
<head>
<title>PHPoC Shield - Web Remote Control for Arduino</title>
<meta name="viewport" content="width=device-width, initial-scale=0.7, maximum-scale=0.7">
<style>
body { text-align: center; }
h1 { font-weight: bold; font-size: 25pt; }
h2 { font-weight: bold; font-size: 15pt; }
button { font-weight: bold; font-size: 15pt; }
</style>
<script>
var BOX_WIDTH = <?echo(int)$wrp_width/3?>;
var BOX_HEIGHT = <?echo(int)$wrp_width/3?>;
var PUSH_RADIUS = <?echo(int)$wrp_width/7?>;
var push_info = [];
var push_text = [ "A", "B", "C", "D", "E", "F", "G", "H", "I" ];
var push_font = "40px Arial";
var ws;
function init()
{
	var remote = document.getElementById("remote");

	remote.width = BOX_WIDTH * 3;
	remote.height = BOX_HEIGHT * 3;

	for(var push_id = 0; push_id < 9; push_id++)
	{
		push_info[push_id] = {state:false, identifier:null, font:push_font, text:push_text[push_id]};
		update_push(push_id, false);
	}

	remote.addEventListener("touchstart", mouse_down);
	remote.addEventListener("touchend", mouse_up);
	remote.addEventListener("touchmove", mouse_move);
	//remote.addEventListener("touchout", mouse_move);

	remote.addEventListener("mousedown", mouse_down);
	remote.addEventListener("mouseup", mouse_up);
	remote.addEventListener("mousemove", mouse_move);
	remote.addEventListener("mouseout", mouse_up);
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

	for(var push_id = 0; push_id < 9; push_id++)
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

	for(var push_id = 0; push_id < 9; push_id++)
		update_push(push_id, false);
}
function ws_onmessage(e_msg)
{
	e_msg = e_msg || window.event; // MessageEvent

	alert("msg : " + e_msg.data);
}
function update_push(push_id, state)
{
	var remote = document.getElementById("remote");
	var ctx = remote.getContext("2d");
	var push = push_info[push_id];
	var cx, cy;

	if(ws && (ws.readyState == 1))
	{
		ctx.strokeStyle = "blue";
		if(state)
			ctx.fillStyle = "blue";
		else
			ctx.fillStyle = "skyblue";
	}
	else
	{
		ctx.strokeStyle = "gray";
		if(state)
			ctx.fillStyle = "gray";
		else
			ctx.fillStyle = "silver";
	}

	cx = BOX_WIDTH * (push_id % 3) + BOX_WIDTH / 2;
	cy = BOX_HEIGHT * parseInt(push_id / 3) + BOX_HEIGHT / 2;

	ctx.beginPath();
	ctx.arc(cx, cy, PUSH_RADIUS, 0, 2 * Math.PI);
	ctx.fill();
	ctx.stroke();

	ctx.font = push.font;
	ctx.textAlign = "center";
	ctx.textBaseline = "middle";
	ctx.fillStyle = "white";
	ctx.fillText(push.text, cx, cy);

	push.state = state;

	if(!state)
		push.identifier = null;

	if(ws && (ws.readyState == 1))
	{
		if(state)
			ws.send(String.fromCharCode(0x41 + push_id)); // 'A' ~ 'I'
		else
			ws.send(String.fromCharCode(0x61 + push_id)); // 'a' ~ 'i'
	}
}

function find_push_id(x, y)
{
	var cx, cy, push_id;

	if((x < 0) || (x >= BOX_WIDTH * 3))
		return 9;

	if((y < 0) || (y >= BOX_WIDTH * 3))
		return 9;

	push_id = parseInt(x / BOX_WIDTH);
	push_id += 3 * parseInt(y / BOX_WIDTH);

	cx = BOX_WIDTH * (push_id % 3) + BOX_WIDTH / 2;
	cy = BOX_HEIGHT * parseInt(push_id / 3) + BOX_HEIGHT / 2;

	if(Math.sqrt((x - cx) * (x - cx) + (y - cy) * (y - cy)) < PUSH_RADIUS)
		return push_id;
	else
		return 9;
}
function mouse_down(event)
{
	var debug = document.getElementById("debug");
	var x, y, push_id;

	//debug.innerHTML = "";

	if(event.changedTouches)
	{
		for(var touch_id = 0; touch_id < event.changedTouches.length; touch_id++)
		{
			var touch = event.changedTouches[touch_id];

			x = touch.pageX - touch.target.offsetLeft;
			y = touch.pageY - touch.target.offsetTop;

			push_id = find_push_id(x, y);

			if(push_id < 9)
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
		x = event.offsetX;
		y = event.offsetY;

		push_id = find_push_id(x, y);

		if(push_id < 9)
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

			for(var push_id = 0; push_id < 9; push_id++)
			{
				if(touch.identifier == push_info[push_id].identifier)
					break;
			}

			if(push_id < 9)
			{
				update_push(push_id, false);
				//debug.innerHTML += ("-" + push_id + "/" + touch.identifier + " ");
			}
		}
	}
	else
	{
		for(var push_id = 0; push_id < 9; push_id++)
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

<p>
<h1>Web Remote Control / Push</h1>
</p>

<canvas id="remote"></canvas><br>

<h2>WebSocket <font id="ws_state" color="gray">CLOSED</font></h2>
<button id="bt_connect" type="button" onclick="connect_onclick();">Connect</button>
<span id="debug"></span>

</body>
</html>
