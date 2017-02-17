<?php
include_once "config.php";
include_once "/lib/sc_envu.php";

$envu = envu_read("nm0", NM0_ENVU_SIZE, NM0_ENVU_OFFSET);

if(!_SERVER("HTTP_REFERER"))
{
	header('HTTP/1.1 403 Forbidden');

	$php_name = _SERVER("SCRIPT_NAME");

	echo "<html>\r\n",
		"<head><title>403 Forbidden</title></head>\r\n",
		"<body>\r\n",
		"<h1>Forbidden</h1>\r\n",
		"<p>You don't have permission to access /$php_name on this server.</p>\r\n",
		"</body></html>\r\n";

	return;
}
$wsm_title         = _POST("wsm_title");
$wsm_width         = _POST("wsm_width");
$wsm_height        = _POST("wsm_height");
$wrp_title         = _POST("wrp_title");
$wrp_width         = _POST("wrp_width");
$wrp_but_name      = substr(_POST("wrp_but_name_all"), 0, -1);
$wrs_title         = _POST("wrs_title");
$wrs_width         = _POST("wrs_width");
$wrs_length        = _POST("wrs_length");
$wrs_value_min     = _POST("wrs_value_min");
$wrs_value_max     = _POST("wrs_value_max");

envu_update($envu, "wsm_title", $wsm_title);
envu_update($envu, "wsm_width", $wsm_width);
envu_update($envu, "wsm_height", $wsm_height);
envu_update($envu, "wrp_title", $wrp_title);
envu_update($envu, "wrp_width", $wrp_width);
envu_update($envu, "wrp_but_name", $wrp_but_name);
envu_update($envu, "wrs_title", $wrs_title);
envu_update($envu, "wrs_width", $wrs_width);
envu_update($envu, "wrs_length", $wrs_length);
envu_update($envu, "wrs_value_min", $wrs_value_min);
envu_update($envu, "wrs_value_max", $wrs_value_max);

envu_write("nm0", $envu, NM0_ENVU_SIZE, NM0_ENVU_OFFSET);
?>
<script type="text/javascript">
	window.self.location.replace("setup_app.php");	
</script>
