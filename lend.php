<?php
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/functions.php');
require_once(__DIR__ . '/bitfinex.php');

$currency = isset($_GET['currency']) ? htmlspecialchars($_GET['currency']) : 'usd';
$config = getConfig($currency);

$bfx = new Bitfinex($config['api_key'], $config['api_secret']);

$current_offers = $bfx->get_offers();

// Something is wrong most likely API key
if (array_key_exists('message', $current_offers)) {
	message($current_offers['message'], 'ERROR');
	exit(1);
}
d($current_offers, 'offers');

// Remove offers that weren't executed for too long
foreach ($current_offers as $item) {
	$id = $item['id'];
	$timestamp = (int) $item['timestamp'];
	$current_timestamp = time();
	$diff_minutes = round(($current_timestamp - $timestamp) / 60);

	if ($config['remove_after'] <= $diff_minutes) {
		message("Removing offer # $id after {$config['remove_after']} minutes");

		$bfx->cancel_offer($id);
	}
}

$balances = $bfx->get_balances();
$available_balance = 0;
d($balances, 'balances');

if ($balances) {
	foreach ($balances as $item) {
		if ($item['type'] === 'deposit' && $item['currency'] === strtolower($config['currency'])) {
			$available_balance = floatval($item['available']);
			$totalBalance = floatval($item['amount']);
			break;
		}
	}
}


// todo: do not lend all money at once
// lend fix amount, vary lending period
// https://docs.google.com/document/d/1wpd6r2izpDdkDa3UBBBgUboJCyLTR85dqRhLydcS-xA/edit#heading=h.c8khatrecsgt
$lendingAmount = $config['minimum_balance'];
$periodMinutes = $config['period'] * 1440;
$numberOfLoansPerPeriod = floor($totalBalance / $lendingAmount);
$lendingInterval = ceil($periodMinutes / $numberOfLoansPerPeriod); // in minutes

// check time since last loan
$lastLoanTimeFilepath = __DIR__ . '/.last_loan_time';
$lastLoanTime = file_exists($lastLoanTimeFilepath) ? file_get_contents($lastLoanTimeFilepath) : 0;
$timeSinceLastLoan = time() - $lastLoanTime;
//d([$lendingAmount, $lendingInterval, $periodMinutes, $numberOfLoansPerPeriod, $timeSinceLastLoan]);die;
if ($timeSinceLastLoan < ($lendingInterval * 60)) {
	message('waiting for lending interval..last loan offer placed before ' . round($timeSinceLastLoan / 60) . ' minutes');
	exit(0);
} else {

// Is there enough balance to lend?
//	if ($available_balance >= $config['minimum_balance']) {
	if ($available_balance >= $lendingAmount) {
		message("Lending availabe balance of $available_balance");

		$lendbook = $bfx->get_lendbook($config['currency']);
//	d($lendbook, 'lendbook');
		$offers = $lendbook['asks'];
		d($offers, 'offers');

		$total_amount = 0;
		$next_rate = 0;
		$next_amount = 0;
		$check_next = FALSE;

		// Find the right rate
		foreach ($offers as $item) {
			// Save next closest item
			if ($check_next) {
				$next_rate = $item['rate'];
				$next_amount = $item['amount'];
				$check_next = FALSE;
			}

			$total_amount += floatval($item['amount']);

			// Possible the closest rate to what we want to lend
			if ($total_amount <= $config['max_total_swaps']) {
				$rate = $item['rate'];
				$check_next = TRUE;
			}
		}

		// Current rate is too low, move closer to the next rate
		// ???
//	if ($next_amount <= $config['max_total_swaps']) {
//		$rate = $next_rate - 0.01;
//	}

		$daily_rate = daily_rate($rate);
		$daily_next_rate = daily_rate($next_rate);

		// if there's a gap between current rate & next rate bigger than one `tick`
		// make the rate as high as possible below the next_rate
		if ($daily_next_rate - $daily_rate > 0.0001) {
			$daily_rate = $daily_next_rate - 0.0001;
			$rate = $daily_rate * 365;
		}
//		d([$daily_rate, $rate, $daily_next_rate, $next_rate], 'rates');
//		die;

		$result = $bfx->new_offer($config['currency'], $lendingAmount, (string) $rate, $config['period'], 'lend');

		// Successfully lent
		if (array_key_exists('id', $result)) {
			message("$lendingAmount {$config['currency']} lent for {$config['period']} days at daily rate of $daily_rate%. Offer id {$result['id']}.");
			file_put_contents($lastLoanTimeFilepath, time());
		} else {
			// Something went wrong
			message($result);
		}
	} else {
		message("Balance of $available_balance {$config['currency']} is not enough to lend.");
	}
}