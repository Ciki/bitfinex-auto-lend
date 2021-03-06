<?php
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/functions.php');
require_once(__DIR__ . '/bitfinex.php');

$config = getConfig();

$bfx = new Bitfinex($config['api_key'], $config['api_secret']);

$current_offers = $bfx->get_offers();

$balances = $bfx->get_balances();
$available_balance = 0;

$currency = @$_GET['currency'] ? htmlspecialchars($_GET['currency']) : '';
$wallet = @$_GET['wallet'] ? htmlspecialchars($_GET['wallet']) : '';
$limit = @$_GET['limit'] ? (int) htmlspecialchars($_GET['limit']) : 10;
$currency_symbol = $currency == 'usd' ? '$' : '&#x0E3F;';


if ($currency && $wallet) {
	foreach ($balances as $item) {
		if ($item['type'] == strtolower($wallet) && $item['currency'] == strtolower($currency)) {
			$available_balance = floatval($item['available']);

			break;
		}
	}

	message("Available balance on $wallet wallet is $currency_symbol$available_balance");

	$history = $bfx->get_history($currency, $limit, $wallet);

	foreach ($history as $item) {
		$amount = round($item['amount'], 4);
		$description = str_replace('Earned fees from user', "Earned $currency_symbol$amount from user", $item['description']);

		message("$description on " . date('d.m.Y @ H:i:s', $item['timestamp']));
	}
} else {
	$total = array();

	if ($currency) {
		$temp = array();

		foreach ($balances as $item) {
			if ($currency == $item['currency']) {
				$temp[] = $item;
				@$total["$currency"] += $item['amount'];
			}
		}

		$balances = $temp;
	} else {
		foreach ($balances as $item) {
			@$total[$item['currency']] += $item['amount'];
		}
	}

	$last_acc = '';
	?>

	<table border="0" cellpadding="4" cellpadding="4">
		<thead>
			<tr>
				<th>Account</th>
	<?php foreach ($total as $key => $val) { ?>
					<th><?= strtoupper($key) ?></th>
	<?php } ?>
			</tr>
		</thead>
		<tbody>
	<?php foreach ($balances as $key => $val) { ?>
		<?php
		$type = $val['type'];

		if ($last_acc != $type) {
			echo '<tr>';
			echo '<td>' . ucfirst($type) . '</td>';

			$last_acc = $type;
		}
		?>
			<td align="right">
		<?= $val['currency'] != 'usd' ? $val['amount'] : round($val['amount'], 2) ?>
			</td>
				<?php
				if ($last_acc != $type) {
					echo '</tr>';
				}
				?>
			<?php } ?>
	</tbody>
	<tfoot>
		<tr>
			<td><b>TOTAL</b></td>
			<?php foreach ($total as $key => $val) { ?>
				<td align="right"><b><?= $key != 'usd' ? $val : round($val, 2) ?></b></td>
			<?php } ?>
		</tr>
	</tfoot>
	</table>

	<?php } ?>
