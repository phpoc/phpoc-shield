<?php

include_once "config.php";
include_once "/lib/sc_envu.php";

function setup_baud(&$envu, $nvp_msg)
{
	global $uart_pid;

	$nvp = explode("=", $nvp_msg);

	if(count($nvp) != 2)
	{
		echo "ws6: invalid setup NVP $nvp_msg\r\n";
		return "";
	}

	$name = ltrim(rtrim($nvp[0]));
	$value = ltrim(rtrim($nvp[1]));

	if($name != "wsm_baud")
	{
		echo "ws6: invalid setup NVP $nvp_msg\r\n";
		return "";
	}

	switch($value)
	{
		case "9600":
		case "19200":
		case "38400":
		case "57600":
		case "115200":
			echo "ws6: set baud rate $value\r\n";
			pid_ioctl($uart_pid, "set baud $value");

			if($value != envu_find($envu, "wsm_baud"))
			{
				echo "ws6: update envu/wsm_baud\r\n";

				envu_update($envu, "wsm_baud", $value);
				envu_write("nm0", $envu, NM0_ENVU_SIZE, NM0_ENVU_OFFSET);
			}

			return $value;

		default:
			echo "ws6: invalid setup NVP $nvp_msg\r\n";
			return "";
	}
}

function busy_wait()
{
	global $uart_pid, $ws_pid;

	$rbuf = "";

	sleep(1);

	pid_send($ws_pid, "\r\n");

	while(pid_read($uart_pid, $rbuf))
	{
		pid_send($ws_pid, "*");
		usleep(40000);
	}

	pid_send($ws_pid, "\r\n");
}

function error_wait()
{
	global $uart_pid, $ws_pid;

	$rbuf = "";

	sleep(1);

	pid_send($ws_pid, "\r\n");

	while(pid_read($uart_pid, $rbuf))
	{
		pid_send($ws_pid, "*");
		usleep(40000);
	}

	pid_send($ws_pid, "\r\n");

	pid_ioctl($uart_pid, "set count fe 0");
}

$envu = envu_read("nm0", NM0_ENVU_SIZE, NM0_ENVU_OFFSET);
if($envu)
	echo $envu;

$wsm_baud = envu_find($envu, "wsm_baud");

$uart_pid = pid_open("/mmap/uart0");
if($wsm_baud)
	pid_ioctl($uart_pid, "set baud $wsm_baud");
else
	pid_ioctl($uart_pid, "set baud 9600");

$ws_pid = pid_open("/mmap/tcp6");
pid_ioctl($ws_pid, "set api ws");
pid_ioctl($ws_pid, "set ws path serial_monitor");
pid_ioctl($ws_pid, "set ws proto uint8.phpoc");
pid_ioctl($ws_pid, "set ws mode 1"); // binary mode
pid_bind($ws_pid, "", 0);

$baud = "";
$rbuf = "";

while(1)
{
	$state = pid_ioctl($ws_pid, "get state");

	if($state == TCP_CONNECTED)
	{
		if(!$baud)
		{
			if(pid_ioctl($ws_pid, "get rxlen \r\n"))
			{
				pid_recv($ws_pid, $rbuf);

				if(!($baud = setup_baud($envu, $rbuf)))
					pid_ioctl($ws_pid, "close");
			}

			continue;
		}

		if(pid_ioctl($uart_pid, "get rxlen \x14\x10"))
		{
			busy_wait();
			continue;
		}

		if(pid_ioctl($uart_pid, "get count fe"))
		{
			error_wait();
			continue;
		}

		$rlen = pid_ioctl($uart_pid, "get rxlen");

		if($rlen && ($rlen > pid_ioctl($ws_pid, "get txfree")))
			$rlen = pid_ioctl($ws_pid, "get txfree");

		if($rlen)
		{
			pid_read($uart_pid, $rbuf, $rlen);
			pid_send($ws_pid, $rbuf);
		}
	}
	else
	{
		pid_read($uart_pid, $rbuf);

		//hexdump($rbuf);

		if($state == TCP_CLOSED)
			pid_listen($ws_pid);

		$baud = "";
	}

	usleep(10000); // long sleep causes uart0 rx buffer overflow
}

?>
