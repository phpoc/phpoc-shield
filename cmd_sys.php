<?php

$sys_date_format = "D M j H:i:s";

function cmd_log_read($id, $cmd)
{ // sys logn read len
	global $log_pid;

	if(count($cmd) < 4)
		return slave_write(ERR_CMD_ARG);

	$pid = $log_pid[$id];
	$rlen = (int)$cmd[3];

	$rbuf = "";
	pid_read($pid, $rbuf, $rlen);

	return slave_write(ERR_OK, $rbuf);
}

function cmd_log_flush($id, $cmd)
{ // sys logn flush
	global $log_pid;

	$pid = $log_pid[$id];

	$rbuf = "";

	while(pid_read($pid, $rbuf))
		;

	return slave_write(ERR_OK);
}

function cmd_log($id, $cmd)
{ // sys logn read/flush
	if(count($cmd) < 3)
		return slave_write(ERR_CMD_ARG);

	switch($cmd[2])
	{
		case "read":
			return cmd_log_read($id, $cmd);
		case "flush":
			return cmd_log_flush($id, $cmd);
		default:
			return slave_write(ERR_CMD_ARG);
	}
}

function cmd_envu($cmd)
{ // sys envu name value
	if(count($cmd) < 4)
		slave_write(ERR_CMD_ARG);

	$envu = envu_read("nm0", NM0_ENVU_SIZE, NM0_ENVU_OFFSET);
	$value = envu_find($envu, $cmd[2]);

	if(!$value)
		echo "add envu ", $cmd[2], " ", $cmd[3], "\r\n";
	else
	{
		if($value == $cmd[3])
			return slave_write(ERR_OK);
		else
			echo "update envu ", $cmd[2], " ", $cmd[3], "\r\n";
	}

	envu_update($envu, $cmd[2], $cmd[3]);
	envu_write("nm0", $envu, NM0_ENVU_SIZE, NM0_ENVU_OFFSET);

	return slave_write(ERR_OK);
}

function cmd_rtc_get($cmd)
{ // sys rtc get date/wday
	if(count($cmd) < 4)
		return slave_write(ERR_CMD_ARG);

	$pid_rtc = pid_open("/mmap/rtc0");
	$date = pid_ioctl($pid_rtc, "get date");
	$wday = pid_ioctl($pid_rtc, "get wday");
	pid_close($pid_rtc);

	switch($cmd[3])
	{
		case "date":
			return slave_write(ERR_OK, $date);
		case "wday":
			if($wday == 0)
				return slave_write(ERR_OK, "7");
			else
				return slave_write(ERR_OK, (string)$wday);
		case "year":
				return slave_write(ERR_OK, substr($date, 0, 4));
		case "month":
				return slave_write(ERR_OK, substr($date, 4, 2));
		case "day":
				return slave_write(ERR_OK, substr($date, 6, 2));
		case "hour":
				return slave_write(ERR_OK, substr($date, 8, 2));
		case "minute":
				return slave_write(ERR_OK, substr($date, 10, 2));
		case "second":
				return slave_write(ERR_OK, substr($date, 12, 2));
		default:
			return slave_write(ERR_CMD_ARG);
	}
}

function cmd_rtc($cmd)
{ // sys rtc get/set
	if(count($cmd) < 3)
		return slave_write(ERR_CMD_ARG);

	if($cmd[2] == "get")
		return cmd_rtc_get($cmd);
	else
		return slave_write(ERR_CMD_ARG);
}

function cmd_date($cmd)
{ // sys date [format]
	global $sys_date_format;

	if(count($cmd) > 2)
	{
		if($cmd[2] == "format")
		{
			slave_write(ERR_OK);

			$rbuf = "";
			if(slave_read_data($rbuf, 10) > 0) // 10 wait_ms
				$sys_date_format = $rbuf;

			return 0;
		}
		else
			return slave_write(ERR_CMD_ARG);
	}
	else
		return slave_write(ERR_OK, date($sys_date_format));
}

function cmd_pkg($cmd)
{
	if(count($cmd) < 3)
		return slave_write(ERR_CMD_ARG);

	$pkg_info = explode(",", PHPOC_PKG_INFO);

	if(count($pkg_info) < 3)
		return slave_write(ERR_CMD_ARG);

	switch($cmd[2])
	{
		case "name":
			return slave_write(ERR_OK, ltrim(rtrim($pkg_info[0])));
		case "ver":
			return slave_write(ERR_OK, ltrim(rtrim($pkg_info[1])));
		case "list":
			return slave_write(ERR_OK, ltrim(rtrim($pkg_info[2])));
		default:
			return slave_write(ERR_CMD_ARG);
	}
}

function cmd_sys($cmd)
{
	if(count($cmd) < 2)
		return slave_write(ERR_CMD_ARG);

	switch($cmd[1])
	{
		case "log0":
			return cmd_log(0, $cmd);
		case "log1":
			return cmd_log(1, $cmd);
		case "envu":
			return cmd_envu($cmd);
		case "rtc":
			return cmd_rtc($cmd);
		case "date":
			return cmd_date($cmd);
		//case "info":
		//	return cmd_info($cmd);
		case "pkg":
			return cmd_pkg($cmd);
		default:
			return slave_write(ERR_CMD_ARG);
	}
}

?>
