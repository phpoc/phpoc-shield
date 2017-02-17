<?php

define("MAX_DATA_LEN", 1024);

$smtp_to_email = "";
$smtp_to_name = "";
$smtp_subject = "";
$smtp_data = "";

function cmd_smtp_server($cmd)
{
	if(count($cmd) < 4)
	{
		esmtp_msa("", 0);
		return slave_write(ERR_OK);
	}

	echo "cmd_smtp_server: ", $cmd[2], " ", $cmd[3], "\r\n";

	esmtp_msa($cmd[2], (int)$cmd[3]);

	return slave_write(ERR_OK);
}

function cmd_smtp_login($cmd)
{
	if(count($cmd) < 4)
		return slave_write(ERR_CMD_ARG);

	echo "cmd_smtp_login: ", $cmd[2], " ", $cmd[3], "\r\n";

	esmtp_auth($cmd[2], $cmd[3]);

	return slave_write(ERR_OK);
}

function cmd_smtp_from($cmd)
{
	slave_write(ERR_OK);

	$email = "";
	$name = "";

	if(!slave_read_data($email, 10))
		return 0;

	if(!slave_read_data($name, 10))
		return 0;

	echo "cmd_smtp_from: \"$name\" <$email>\r\n";

	esmtp_account($email, $name);

	return 0;
}

function cmd_smtp_to($cmd)
{
	global $smtp_to_email, $smtp_to_name;

	slave_write(ERR_OK);

	$email = "";
	$name = "";

	if(!slave_read_data($email, 10))
		return 0;

	if(!slave_read_data($name, 10))
		return 0;

	echo "cmd_smtp_to: \"$name\" <$email>\r\n";

	$smtp_to_email = $email;
	$smtp_to_name = $name;

	return 0;
}

function cmd_smtp_subject($cmd)
{
	global $smtp_subject;

	slave_write(ERR_OK);

	$rlen = slave_read_data($smtp_subject, 10);

	echo "cmd_smtp_subject: $smtp_subject\r\n";

	return 0;
}

function cmd_smtp_data($cmd)
{
	global $smtp_data;

	if(count($cmd) > 2)
	{
		if($cmd[2] == "begin")
		{
			echo "cmd_smtp_data: begin\r\n";
			$smtp_data = "";
			return slave_write(ERR_OK);
		}
		else
			return slave_write(ERR_CMD_ARG);
	}

	slave_write(ERR_OK);

	$rbuf = "";
	$rlen = slave_read_data($rbuf, 10);

	echo "$rbuf";

	if((strlen($smtp_data) + $rlen) <= MAX_DATA_LEN)
		$smtp_data .= $rbuf;

	return 0;
}

function cmd_smtp_send($cmd)
{
	global $smtp_to_email, $smtp_to_name;
	global $smtp_subject, $smtp_data;
	global $tcp_pid;

	if($tcp_pid[TCP_ID_SMTP])
	{
		pid_close($tcp_pid[TCP_ID_SMTP]);
		$tcp_pid[TCP_ID_SMTP] = 0;
	}

	if(count($cmd) > 2)
	{
		if($cmd[2] == "ip6")
			$ip6 = true;
		else
			return slave_write(ERR_CMD_ARG);
	}
	else
		$ip6 = false;

	esmtp_setup(UDP_ID_DNS, TCP_ID_SMTP, "", $ip6);

	esmtp_start($smtp_to_email, $smtp_to_name, $smtp_subject, $smtp_data);
	return slave_write(ERR_OK);
}

function cmd_smtp_status($cmd)
{
	$msg = esmtp_loop();

	if($msg === false)
		return slave_write(ERR_OK);
	else
	{
		if(!$msg)
			return slave_write(ERR_OK, "0");
		else
			return slave_write(ERR_OK, $msg);
	}
}

function cmd_smtp($cmd)
{
	if(count($cmd) < 2)
		return slave_write(ERR_CMD_ARG);

	switch($cmd[1])
	{
		case "server":
			return cmd_smtp_server($cmd);
		case "login":
			return cmd_smtp_login($cmd);
		case "from":
			return cmd_smtp_from($cmd);
		case "to":
			return cmd_smtp_to($cmd);
		case "subject":
			return cmd_smtp_subject($cmd);
		case "data":
			return cmd_smtp_data($cmd);
		case "send":
			return cmd_smtp_send($cmd);
		case "status":
			return cmd_smtp_status($cmd);
		default:
			return slave_write(ERR_CMD_ARG);
	}
}

?>
