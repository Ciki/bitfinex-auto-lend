<?php

/**
 * @internal Called from config.php only
 * @param array $config
 * @return array
 */
function appendLocalConfig(array $config)
{
	$config['api_key'] = '<your api key>';
	$config['api_secret'] = '<your secret key>';
	return $config;
}

