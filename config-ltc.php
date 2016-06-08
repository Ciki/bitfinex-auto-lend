<?php
require_once(__DIR__ . '/config.php');

$config['currency'] = 'LTC'; // Currency to lend
$config['period'] = 2; // Number of days to lend
$config['minimum_balance'] = 8; // Minimum balance to be able to lend (equivalent of $50)
$config['remove_after'] = 50; // Max number of total swaps to check for a closest rate *
$config['max_total_swaps'] = 2000; // Minutes after unexecuted offer gets cancelled

// * If there are e.g. total of 1800 swaps at 1%/day and 2200
// swaps at 1.001%/day the script chooses the lower rate, because
// we don't want to go beyond the rate of 2000 swaps.