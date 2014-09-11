<?php
header("Content-Type: image/gif");
echo base64_decode("R0lGODlhAQABAPAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==");

require_once("classes/db.class.php");

$language = isset($_GET['l']) ? $_GET['l'] : exit;
$language = ($language == "GB") ? 'en' : $language;
//$language = ($language == "pt") ? "pt_BR" : $language;

$error = isset($_GET['e']) ? $_GET['e'] : exit;
$correction = isset($_GET['c']) ? $_GET['c'] : null;
$ignore = isset($_GET['i']) ? $_GET['i'] : 0;
$sentence = isset($_GET['s']) ? $_GET['s'] : null;

// Record spelling correction

$db = db::singleton("localhost", "services", "msH7Ikav93mdHDpw93", "services");

$date = new DateTime();
$time = $date->format('Y-m-d H:i:s');

$array['language'] = $language;
$array['incorrect'] = $error;
$array['correction'] = $correction;
$array['sentence'] = $sentence;
$array['ignore'] = $ignore;
$array['time'] = $time;

$db->insert($array, "spellng")->run();