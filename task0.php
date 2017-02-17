<?php

if(_SERVER("REQUEST_METHOD"))
	exit; // avoid php execution via http request

include_once "config.php";
include_once "/lib/sc_envu.php";
include_once "/lib/sn_dns.php";
include_once "/lib/sn_esmtp.php";
include_once "cmd.php";
include_once "cmd_dns.php";
include_once "cmd_tcp.php";
include_once "cmd_smtp.php";
include_once "cmd_sys.php";

function cmd_net($id, $cmd)
{
	if(count($cmd) < 3)
		return slave_write(ERR_CMD_ARG);

	if($cmd[1] != "get")
		return slave_write(ERR_CMD_ARG);

	if($id == 0)
	{
		if((int)ini_get("init_net0"))
			$pid = pid_open("/mmap/net0");
		else
		{
			if($cmd[2] == "mode")
				return slave_write(ERR_OK, "");
			else
			if($cmd[2] == "speed")
				return slave_write(ERR_OK, "0");

			$pid = pid_open("/mmap/net1");
		}
	}
	else
		$pid = pid_open("/mmap/net1");

	switch($cmd[2])
	{
		case "mode":
			slave_write(ERR_OK, pid_ioctl($pid, "get mode"));
			break;
		case "speed":
			slave_write(ERR_OK, (string)pid_ioctl($pid, "get speed"));
			break;
		case "hwaddr":
			slave_write(ERR_OK, pid_ioctl($pid, "get hwaddr"));
			break;
		case "ipaddr":
			slave_write(ERR_OK, pid_ioctl($pid, "get ipaddr"));
			break;
		case "netmask":
			slave_write(ERR_OK, pid_ioctl($pid, "get netmask"));
			break;
		case "gwaddr":
			slave_write(ERR_OK, pid_ioctl($pid, "get gwaddr"));
			break;
		case "nsaddr":
			slave_write(ERR_OK, pid_ioctl($pid, "get nsaddr"));
			break;
		case "ipaddr6":
			if(count($cmd) > 3)
				slave_write(ERR_OK, pid_ioctl($pid, "get ipaddr6 %1", $cmd[3]));
			else
				slave_write(ERR_OK, pid_ioctl($pid, "get ipaddr6 1"));
			break;
		case "prefix6":
			slave_write(ERR_OK, (string)pid_ioctl($pid, "get prefix6"));
			break;
		case "gwaddr6":
			slave_write(ERR_OK, pid_ioctl($pid, "get gwaddr6"));
			break;
		case "nsaddr6":
			slave_write(ERR_OK, pid_ioctl($pid, "get nsaddr6"));
			break;
		default:
			slave_write(ERR_CMD_ARG);
			break;
	}

	pid_close($pid);
}

function cmd_tcpn($cmd)
{
	global $tcp_pid;

	if(strlen($cmd[0]) == 4)
	{
		$id = bin2int($cmd[0], 3, 1) - 0x30;

		if(($id >= 0) && ($id <= 5)) // 0~5 ?
		{
			if(!$tcp_pid[$id])
			{
				$tcp_pid[$id] = pid_open("/mmap/tcp$id");

				if($id == TCP_ID_SSL)
					pid_ioctl($tcp_pid[$id], "set api ssl");

				if($id == TCP_ID_SSH)
					pid_ioctl($tcp_pid[$id], "set api ssh");
			}

			cmd_tcp($id, $cmd);
		}
		else
			slave_write(ERR_CMD_UND);
	}
	else
		slave_write(ERR_CMD_UND);
}

echo "PHPoC Shield for Arduino / P4S-348\r\n";

$pid_uio0 = pid_open("/mmap/uio0");
pid_ioctl($pid_uio0, "set 30 mode led_net1_act");
pid_ioctl($pid_uio0, "set 31 mode led_net0_act");
pid_close($pid_uio0);

$spi_pid = pid_open("/mmap/spi0");

$log_pid = array(0, 0, 0, 0);
$log_pid[0] = pid_open("/mmap/log0");
$log_pid[1] = pid_open("/mmap/log1");
$log_pid[2] = pid_open("/mmap/log2");
$log_pid[3] = pid_open("/mmap/log3");

$tcp_pid = array(0, 0, 0, 0, 0, 0);

pid_ioctl($spi_pid, "set mode 3");
pid_ioctl($spi_pid, "set role slave");
pid_ioctl($spi_pid, "req start");

dns_setup(UDP_ID_DNS, "");
esmtp_setup(UDP_ID_DNS, TCP_ID_SMTP, "");

$rbuf = "";

while(($rlen = pid_ioctl($spi_pid, "get rxlen")))
	pid_read($spi_pid, $rbuf);

while(1)
{
	if(slave_read_command($rbuf, 0))
	{
		$cmd = explode(" ", $rbuf);

		if(!count($cmd))
			slave_write(ERR_CMD_UND);

		if(substr($cmd[0], 0, 3) === "tcp")
			cmd_tcpn($cmd);
		else
		{
			switch($cmd[0])
			{
				case "net0":
					cmd_net(0, $cmd);
					break;
				case "net1":
					cmd_net(1, $cmd);
					break;
				case "dns":
					cmd_dns($cmd);
					break;
				case "smtp":
					cmd_smtp($cmd);
					break;
				case "sys":
					cmd_sys($cmd);
					break;
				default:
					slave_write(ERR_CMD_UND);
					break;
			}
		}
	}
}

?>
