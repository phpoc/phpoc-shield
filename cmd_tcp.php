<?php

function cmd_tcp_get($id, $cmd)
{
	global $tcp_pid;

	if(count($cmd) < 3)
		return slave_write(ERR_CMD_ARG);

	$pid = $tcp_pid[$id];

	switch($cmd[2])
	{
		case "state":
			return slave_write(ERR_OK, (string)pid_ioctl($pid, "get state"));

		case "rxlen":
			if(count($cmd) > 3)
				return slave_write(ERR_OK, (string)pid_ioctl($pid, "get rxlen %1", hex2bin($cmd[3])));
			else
				return slave_write(ERR_OK, (string)pid_ioctl($pid, "get rxlen"));

		case "txlen":
			$txlen = pid_ioctl($pid, "get txbuf") - pid_ioctl($pid, "get txfree");
			return slave_write(ERR_OK, (string)$txlen);

		case "txfree":
			return slave_write(ERR_OK, (string)pid_ioctl($pid, "get txfree"));

		case "dstport":
			return slave_write(ERR_OK, (string)pid_ioctl($pid, "get dstport"));

		case "srcport":
			return slave_write(ERR_OK, (string)pid_ioctl($pid, "get srcport"));

		case "dstaddr":
			return slave_write(ERR_OK, pid_ioctl($pid, "get dstaddr"));

		case "srcaddr":
			return slave_write(ERR_OK, pid_ioctl($pid, "get srcaddr"));

		case "ssh":
			if(count($cmd) < 4)
				return slave_write(ERR_CMD_ARG);
			if($cmd[3] == "username")
				return slave_write(ERR_OK, pid_ioctl($pid, "get ssh username"));
			else
			if($cmd[3] == "password")
				return slave_write(ERR_OK, pid_ioctl($pid, "get ssh password"));
			else
				return slave_write(ERR_CMD_ARG);

		default:
			return slave_write(ERR_CMD_ARG);
	}
}

function cmd_tcp_set_api($id, $cmd)
{
	global $tcp_pid;

	if(count($cmd) < 4)
		return slave_write(ERR_CMD_ARG);

	//echo "cmd_tcp_set_api: ", $cmd[3], "\r\n";

	$pid = $tcp_pid[$id];

	switch($cmd[3])
	{
		case "tcp":
			pid_ioctl($pid, "set api tcp");
			return slave_write(ERR_OK);
		case "telnet":
			pid_ioctl($pid, "set api telnet");
			return slave_write(ERR_OK);
		case "ws":
			pid_ioctl($pid, "set api ws");
			return slave_write(ERR_OK);
		case "ssl":
			pid_ioctl($pid, "set api ssl");
			return slave_write(ERR_OK);
		case "ssh":
			pid_ioctl($pid, "set api ssh");
			return slave_write(ERR_OK);
		default:
			return slave_write(ERR_CMD_ARG);
	}
}

function cmd_tcp_set_ws($id, $cmd)
{
	global $tcp_pid;

	if(count($cmd) < 5)
		return slave_write(ERR_CMD_ARG);

	echo "cmd_tcp_set_ws: ", $cmd[3], " ", $cmd[4], "\r\n";

	$pid = $tcp_pid[$id];

	switch($cmd[3])
	{
		case "path":
			pid_ioctl($pid, "set ws path %1", $cmd[4]);
			return slave_write(ERR_OK);
		case "proto":
			pid_ioctl($pid, "set ws proto %1", $cmd[4]);
			return slave_write(ERR_OK);
		case "origin":
			return slave_write(ERR_OK);
		case "mode":
			pid_ioctl($pid, "set ws mode %1", $cmd[4]);
			return slave_write(ERR_OK);
			break;
		default:
			return slave_write(ERR_CMD_ARG);
	}
}

function cmd_tcp_set_ssl($id, $cmd)
{
	global $tcp_pid;

	if($id != TCP_ID_SSL)
		return slave_write(ERR_CMD_ARG);

	if(count($cmd) < 5)
		return slave_write(ERR_CMD_ARG);

	//echo "cmd_tcp_set_ssl: ", $cmd[3], "\r\n";

	$pid = $tcp_pid[$id];

	if($cmd[3] == "method")
	{
		switch($cmd[4])
		{
			case "ssl3_client":
				pid_ioctl($pid, "set ssl method ssl3_client");
				return slave_write(ERR_OK);
			case "ssl3_server":
				pid_ioctl($pid, "set ssl method ssl3_server");
				return slave_write(ERR_OK);
			case "tls1_client":
				pid_ioctl($pid, "set ssl method tls1_client");
				return slave_write(ERR_OK);
			case "tls1_server":
				pid_ioctl($pid, "set ssl method tls1_server");
				return slave_write(ERR_OK);
			default:
				return slave_write(ERR_CMD_ARG);
		}
	}
	else
		return slave_write(ERR_CMD_ARG);
}

function cmd_tcp_set_ssh($id, $cmd)
{
	global $tcp_pid;

	if($id != TCP_ID_SSH)
		return slave_write(ERR_CMD_ARG);

	if(count($cmd) < 5)
		return slave_write(ERR_CMD_ARG);

	//echo "cmd_tcp_set_ssh: ", $cmd[3], "\r\n";

	$pid = $tcp_pid[$id];

	if($cmd[3] == "auth")
	{
		switch($cmd[4])
		{
			case "accept":
				pid_ioctl($pid, "set ssh auth accept");
				return slave_write(ERR_OK);
			case "reject":
				pid_ioctl($pid, "set ssh auth reject");
				return slave_write(ERR_OK);
			default:
				return slave_write(ERR_CMD_ARG);
		}
	}
	else
		return slave_write(ERR_CMD_ARG);
}

function cmd_tcp_set($id, $cmd)
{
	global $tcp_pid;

	if(count($cmd) < 3)
		return slave_write(ERR_CMD_ARG);

	$pid = $tcp_pid[$id];

	switch($cmd[2])
	{
		/*
		case "nodelay":
			pid_ioctl($pid, "set nodelay");
			return slave_write(ERR_OK);
		case "rtt":
			pid_ioctl($pid, "set rtt");
			return slave_write(ERR_OK);
		*/
		case "api":
			return cmd_tcp_set_api($id, $cmd);
		case "ws":
			return cmd_tcp_set_ws($id, $cmd);
		case "ssl":
			return cmd_tcp_set_ssl($id, $cmd);
		case "ssh":
			return cmd_tcp_set_ssh($id, $cmd);
		default:
			return slave_write(ERR_CMD_ARG);
	}
}

function cmd_tcp_listen($id, $cmd)
{
	global $tcp_pid;

	if(count($cmd) < 3)
		return slave_write(ERR_CMD_ARG);

	$pid = $tcp_pid[$id];

	printf("cmd_tcp_listen: %s\r\n", $cmd[2]);

	if(pid_ioctl($pid, "get state") > TCP_LISTEN)
	{
		echo "cmd_tcp_listen: close\r\n";
		pid_ioctl($pid, "close");
		usleep(10000);
	}

	pid_bind($pid, "", (int)$cmd[2]);
	pid_listen($pid);

	return slave_write(ERR_OK);
}

function cmd_tcp_connect($id, $cmd)
{
	global $tcp_pid;

	if(count($cmd) < 4)
		return slave_write(ERR_CMD_ARG);

	$pid = $tcp_pid[$id];

	printf("cmd_tcp_connect: %d %s %s\r\n", $id, $cmd[2], $cmd[3]);

	if(pid_ioctl($pid, "get state"))
	{
		echo "cmd_tcp_connect: close\r\n";
		pid_ioctl($pid, "close");
		usleep(10000);
	}

	pid_bind($pid, "", 0); // allocate new local port
	pid_connect($pid, $cmd[2], (int)$cmd[3]);

	return slave_write(ERR_OK);
}

function cmd_tcp_send($id, $cmd)
{
	global $tcp_pid;

	$pid = $tcp_pid[$id];

	//echo "cmd_tcp_send:\r\n";

	if($id == TCP_ID_SSL)
	{
		if(pid_ioctl($pid, "get state") != SSL_CONNECTED)
			return slave_write(ERR_EPIPE);
	}
	else
	if($id == TCP_ID_SSH)
	{
		if(pid_ioctl($pid, "get state") != SSH_CONNECTED)
			return slave_write(ERR_EPIPE);
	}
	else
	{
		if(pid_ioctl($pid, "get state") != TCP_CONNECTED)
			return slave_write(ERR_EPIPE);
	}

	slave_write(ERR_OK);

	$rbuf = "";
	$rlen = slave_read_data($rbuf, 10); // 10 wait_ms

	//hexdump($rbuf);

	if($rlen)
		return pid_send($pid, $rbuf, $rlen);
	else
		return 0;
}

function cmd_tcp_recv($id, $cmd)
{
	global $tcp_pid;

	if(count($cmd) < 3)
		return slave_write(ERR_CMD_ARG);

	$pid = $tcp_pid[$id];
	$rlen = (int)$cmd[2];

	//echo "cmd_tcp_recv: $rlen\r\n";

	$rbuf = "";
	pid_recv($pid, $rbuf, $rlen);

	return slave_write(ERR_OK, $rbuf);
}

function cmd_tcp_peek($id, $cmd)
{
	global $tcp_pid;

	if(count($cmd) < 3)
		return slave_write(ERR_CMD_ARG);

	$pid = $tcp_pid[$id];
	$rlen = (int)$cmd[2];

	echo "cmd_tcp_peek: $rlen\r\n";

	$rbuf = "";
	pid_peek($pid, $rbuf, $rlen);

	return slave_write(ERR_OK, $rbuf);
}

function cmd_tcp_close($id, $cmd)
{
	global $tcp_pid;

	$pid = $tcp_pid[$id];

	//printf("cmd_tcp_close: %d\r\n", $id);

	if($pid)
		pid_ioctl($pid, "close");

	return slave_write(ERR_OK);
}

function cmd_tcp($id, $cmd)
{
	if(count($cmd) < 2)
		return slave_write(ERR_CMD_ARG);

	switch($cmd[1])
	{
		case "get":
			return cmd_tcp_get($id, $cmd);

		case "set":
			return cmd_tcp_set($id, $cmd);

		case "listen":
			return cmd_tcp_listen($id, $cmd);

		case "connect":
			return cmd_tcp_connect($id, $cmd);

		case "send":
			return cmd_tcp_send($id, $cmd);

		case "recv":
			return cmd_tcp_recv($id, $cmd);

		case "peek":
			return cmd_tcp_peek($id, $cmd);

		case "close":
			return cmd_tcp_close($id, $cmd);

		default:
			return slave_write(ERR_CMD_ARG);
	}
}

?>
