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

function cmd_net01($pid, $cmd)
{
	switch($cmd[2])
	{
		case "mode":
			return slave_write(ERR_OK, pid_ioctl($pid, "get mode"));
		case "speed":
			return slave_write(ERR_OK, (string)pid_ioctl($pid, "get speed"));
		case "hwaddr":
			return slave_write(ERR_OK, pid_ioctl($pid, "get hwaddr"));
		case "ipaddr":
			return slave_write(ERR_OK, pid_ioctl($pid, "get ipaddr"));
		case "netmask":
			return slave_write(ERR_OK, pid_ioctl($pid, "get netmask"));
		case "gwaddr":
			return slave_write(ERR_OK, pid_ioctl($pid, "get gwaddr"));
		case "nsaddr":
			return slave_write(ERR_OK, pid_ioctl($pid, "get nsaddr"));
		case "ipaddr6":
			if(count($cmd) > 3)
				return slave_write(ERR_OK, pid_ioctl($pid, "get ipaddr6 %1", $cmd[3]));
			else
				return slave_write(ERR_OK, pid_ioctl($pid, "get ipaddr6 1"));
		case "prefix6":
			return slave_write(ERR_OK, (string)pid_ioctl($pid, "get prefix6"));
		case "gwaddr6":
			return slave_write(ERR_OK, pid_ioctl($pid, "get gwaddr6"));
		case "nsaddr6":
			return slave_write(ERR_OK, pid_ioctl($pid, "get nsaddr6"));
		default:
			return slave_write(ERR_CMD_ARG);
	}
}

function cmd_net0($pid, $cmd)
{
	cmd_net01($pid, $cmd);
}

function cmd_net1($pid, $cmd)
{
	switch($cmd[2])
	{
		case "ch":
			return slave_write(ERR_OK, (string)pid_ioctl($pid, "get ch"));
		case "ssid":
			return slave_write(ERR_OK, pid_ioctl($pid, "get ssid"));
		case "rssi":
			return slave_write(ERR_OK, (string)pid_ioctl($pid, "get rssi"));
		case "rsna":
			return slave_write(ERR_OK, pid_ioctl($pid, "get rsna"));
		case "akm":
			return slave_write(ERR_OK, pid_ioctl($pid, "get akm"));
		case "cipher":
			return slave_write(ERR_OK, pid_ioctl($pid, "get cipher"));
		default:
			return cmd_net01($pid, $cmd);
	}
}

function cmd_net($id, $cmd)
{
	if(count($cmd) < 3)
		return slave_write(ERR_CMD_ARG);

	if($cmd[1] != "get")
		return slave_write(ERR_CMD_ARG);

	if($id == 0)
	{
		if((int)ini_get("init_net0"))
		{
			$pid = pid_open("/mmap/net0");
			cmd_net0($pid, $cmd);
			pid_close($pid);
		}
		else
		{
			switch($cmd[2])
			{
				case "mode":
					slave_write(ERR_OK, "");
					break;
				case "speed":
					slave_write(ERR_OK, "0");
					break;
				default:
					$pid = pid_open("/mmap/net1");
					cmd_net01($pid, $cmd);
					pid_close($pid);
					break;
					
			}
		}
	}
	else
	{
		$pid = pid_open("/mmap/net1");
		cmd_net1($pid, $cmd);
		pid_close($pid);
	}
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

echo "PHPoC Shield for Arduino / ", system("uname -i"), "\r\n";

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
