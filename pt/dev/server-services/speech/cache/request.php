<?php
header('Content-type: application/json; charset=UTF-8');
$vars = $_GET;
$id = $vars['id'];
$callback = $vars['callback'];
echo $callback;
echo "(";
readfile("$id.json");
echo ");";