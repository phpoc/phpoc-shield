<?php

define("ERR_OK",        0);
define("ERR_CMD_WAIT", 10);
define("ERR_CMD_UND",  11);
define("ERR_CMD_ARG",  12);
define("ERR_CMD_DATA", 13);
define("ERR_EPIPE",    14);

define("TCP_ID_SSL",   0);
define("TCP_ID_SSH",   1);
define("TCP_ID_SMTP",  0);
define("UDP_ID_DNS",   4);

function err2str($err)
{
	switch($err)
	{
		case ERR_OK:
			return "";
		case ERR_CMD_UND:
			return "unknown command";
		case ERR_CMD_ARG:
			return "invalid argument";
		case ERR_CMD_DATA:
			return "invalid command data";
		case ERR_EPIPE:
			return "broken pipe";
		default:
			return "error $err";
	}
}

function slave_write($err, $data = "")
{
	global $spi_pid;

	if($err)
	{
		$head32 = 0xa0000000; // data & NAK bit set, csum bit clear

		if($err == ERR_CMD_WAIT)
		{
			$wait = (int)$data;
			if($wait < 1)
				$wait = 1;
			if($wait > 30)
				$wait = 30;

			$data = sprintf("W%03u", $wait);
			//echo "cmd_wait: $wait\r\n";
		}
		else
		{
			$data = sprintf("E%03u%s", $err, err2str($err));
			echo "cmd_error: ", err2str($err), "\r\n";
		}
	}
	else
		$head32 = 0x80000000; // data bit set, csum bit clear

	$head32 |= (strlen($data) << 16);
	$wbuf = int2bin($head32, 4, true) . $data;

	return pid_write($spi_pid, $wbuf);
}

function slave_rxlen_wait($rxlen, $wait_ms)
{
	global $spi_pid;

	if($rxlen && $wait_ms)
	{
		while(pid_ioctl($spi_pid, "get rxlen") < $rxlen)
		{
			usleep(1000);
			$wait_ms--;

			if(!$wait_ms)
			{
				echo "slave_read: rxlen wait timeout\r\n";
				return 0;
			}
		}
	}

	return pid_ioctl($spi_pid, "get rxlen");
}

function slave_read(&$rbuf, $wait_ms, $is_cmd)
{
	global $spi_pid;

	if($wait_ms && !slave_rxlen_wait(4, $wait_ms))
		return 0;

	if(pid_ioctl($spi_pid, "get rxlen") < 4)
		return 0;

	pid_peek($spi_pid, $rbuf, 4);

	$head32 = bin2int($rbuf, 0, 4, true);
	$len12  = ($head32 >> 16) & 0x0fff;

	if(!$is_cmd && !($head32 & 0x80000000))
	{
		// skip reading data if command head is received when we read data.
		echo "slave_read: skip read data\r\n";
		return 0;
	}

	pid_read($spi_pid, $rbuf, 4); // drop head

	if($len12)
	{
		if($wait_ms && !slave_rxlen_wait($len12, $wait_ms))
			return 0;

		pid_read($spi_pid, $rbuf, $len12);

		if($is_cmd && ($head32 & 0x80000000))
		{
			// drop data if data head is received when we read command.
			echo "slave_read: drop data\r\n";
			$rbuf = "";
			return 0;
		}
		else
			return $len12;
	}
	else
	{
		$rbuf = "";
		return 0;
	}
}

function slave_read_command(&$rbuf, $wait_ms)
{
	return slave_read($rbuf, $wait_ms, true);
}

function slave_read_data(&$rbuf, $wait_ms)
{
	return slave_read($rbuf, $wait_ms, false);
}

?>
