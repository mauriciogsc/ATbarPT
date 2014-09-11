<?php
$loadAvg = sys_getloadavg();
require("../lib/db.class.php");
$database = db::singleton();
$database->single("INSERT INTO studybar.log_tts (host, ip, req_size, cpu_load) VALUES('" . $database->real_escape_string( gethostbyaddr($_SERVER['REMOTE_ADDR']) ) . "', '" . $database->real_escape_string($_SERVER['REMOTE_ADDR']) . "', '" . filesize("loadWriter.php") . "', '" . $database->real_escape_string($loadAvg[0]) . "')");

?>