<?php

$dns_setup_server = "";

function cmd_dns_server($cmd)
{
	global $dns_setup_server;

	if(count($cmd) < 3)
		return slave_write(ERR_CMD_ARG);

	$addr = $cmd[2];

	if(inet_pton($addr) === false)
		return slave_write(ERR_CMD_ARG);

	$dns_setup_server = $addr;
	return slave_write(ERR_OK);
}

function cmd_dns_query($cmd)
{
	global $dns_setup_server;

	if(count($cmd) < 4)
		return slave_write(ERR_CMD_ARG);

	switch(strtoupper($cmd[2]))
	{
		case "A":
			$rr_type = RR_A;
			break;
		case "AAAA":
			$rr_type = RR_AAAA;
			break;
		default:
			return slave_write(ERR_CMD_ARG);
	}

	$query_name = $cmd[3];

	if(inet_pton($query_name) !== false)
		return slave_write(ERR_OK, $query_name);
	else
	{
		if(count($cmd) > 4)
		{
			$wait_ms = (int)$cmd[4];
			if(!$wait_ms)
				return slave_write(ERR_CMD_ARG);
		}
		else
			$wait_ms = 2000;

		slave_write(ERR_CMD_WAIT, (string)($wait_ms / 1000 + 1));

		if($rr_type == RR_A)
		{
			dns_setup(UDP_ID_DNS, $dns_setup_server, false);

			$rr = dns_lookup($query_name, RR_A, $wait_ms);

			if($rr == $query_name)
				return slave_write(ERR_OK, "0.0.0.0");
			else
				return slave_write(ERR_OK, $rr);
		}
		else
		{
			dns_setup(UDP_ID_DNS, $dns_setup_server, true);

			$rr = dns_lookup($query_name, RR_AAAA, $wait_ms);

			if($rr == $query_name)
				return slave_write(ERR_OK, "::0");
			else
				return slave_write(ERR_OK, $rr);
		}
	}
}

function cmd_dns($cmd)
{
	if(count($cmd) < 2)
		return slave_write(ERR_CMD_ARG);

	switch($cmd[1])
	{
		case "server":
			return cmd_dns_server($cmd);
		case "query":
			return cmd_dns_query($cmd);
		default:
			return slave_write(ERR_CMD_ARG);
	}
}

?>
