<?php


function d($array, $label = NULL)
{
	echo $label . '<br>';
	echo '<pre>';
	print_r($array);
	echo '</pre>';
}


function message($message, $level = 'INFO')
{
	echo "$level: $message<br />";

	$log = date('d.m.Y H:i:s') . ': ' . $message . "\r\n";
	file_put_contents(__DIR__ . "/$level.log", $log, FILE_APPEND);
}


function daily_rate($rate)
{
	return round($rate / 365, 4);
}

