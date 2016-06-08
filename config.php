<?php
// * If there are e.g. total of 12000 swaps at 1%/day and 22000
// swaps at 1.001%/day the script chooses the lower rate, because
// we don't want to go beyond the rate of 20000 swaps.


include __DIR__ . '/config.local.php';

function getConfig($currency = 'usd')
{
	$config = [
		'usd' => [
			'currency' => 'USD', // Currency to lend
			'period' => 2, // Number of days to lend
			'minimum_balance' => 50, // Minimum balance to be able to lend (bitfinex constant)
			'remove_after' => 50, // Minutes after unexecuted offer gets cancelled
			'max_total_swaps' => 20000, // Max number of total swaps to check for a closest rate *
		],
		'ltc' => [
			'currency' => 'LTC',
			'period' => 2,
			'minimum_balance' => 8,
			'remove_after' => 50,
			'max_total_swaps' => 2000,
		],
		'btc' => [
			'currency' => 'BTC',
			'period' => 2,
			'minimum_balance' => 0.1,
			'remove_after' => 50,
			'max_total_swaps' => 50,
		],
	];

	$config = appendLocalConfig($config[$currency]);
	return $config;
}

