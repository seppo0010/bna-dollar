<?php
if (isset($argv[3]) || isset($_GET['test'])) {
	define('BNA_URL', "sample{day}{month}{year}.html");
	define('DB_HOST', 'localhost');
	define('DB_USER', 'root');
	define('DB_PASS', '');
	define('DB_NAME', 'bna_test');
} else {
	define('BNA_URL', "http://www.bna.com.ar/Cotizador/HistoricoPrincipales?id=billetes&fecha={day}%2F{month}%2F{year}&filtroEuro=1&filtroDolar=1");
	define('DB_HOST', '');
	define('DB_USER', '');
	define('DB_PASS', '');
	define('DB_NAME', '');
}

define('DB_TABLE_NAME', 'currency');
define('DB_FIELD_DATE', 'date');
define('DB_FIELD_DOLLAR_BUY', 'dollar_buy');
define('DB_FIELD_DOLLAR_SELL', 'dollar_sell');
define('DB_FIELD_EURO_BUY', 'euro_buy');
define('DB_FIELD_EURO_SELL', 'euro_sell');

define('DOLLAR', 'Dolar U.S.A');
define('EURO', 'Euro');
define('BUY', 'Compra');
define('SELL', 'Venta');
define('DAY', 24 * 60 * 60);
function getValues($year, $month, $day) {
	$bna_url = str_replace(
		'{day}',
		str_pad($day, 2, 0, STR_PAD_LEFT),
		str_replace(
			'{month}',
			str_pad($month, 2, 0, STR_PAD_LEFT),
			str_replace(
			'{year}',
			$year,
			BNA_URL
			)
		)
	);
	$data = file_get_contents($bna_url);
	$DOM = new DOMDocument;
	$DOM->loadHTML($data);
	$xpath = new DOMXPath($DOM);
	$nodes = $xpath->query('//tr');
	$keys = NULL;
	$retval = array(
		DOLLAR => array(),
		EURO => array(),
	);
	foreach ($nodes as $row) {
		$values = array();
		foreach ($row->childNodes as $cell) {
			$values[] = trim($cell->textContent);
		}
		if ($keys === NULL) {
			$keys = $values;
		} else {
			$valuesDate = array_combine($keys, $values);
			unset($valuesDate['']);
			if ($valuesDate[BUY] === BUY) {
				continue;
			}
			$dateFragments = explode('/', $valuesDate['Fecha']);
			$date = date('Y-m-d', mktime(0, 0, 0, $dateFragments[1], $dateFragments[0], $dateFragments[2]));
			$retval[$valuesDate['Monedas']][$date] = array(
				BUY => floatval(str_replace(',', '.', $valuesDate[BUY])),
				SELL => floatval(str_replace(',', '.', $valuesDate[SELL])),
			);
		}
	}
	return $retval;
}

function insertValues($values) {
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
	if (!$link) {
		return 1;
	}
	if (!mysqli_select_db($link, DB_NAME)) {
		return 2;
	}
	$value = $values[DOLLAR];
	foreach ($value as $date => $val) {
		if (!mysqli_query($link, sprintf('DELETE FROM %s WHERE %s = \'%s\'',
			DB_TABLE_NAME,
			DB_FIELD_DATE,
			mysqli_real_escape_string($link, $date)
		))) {
			return 3;
		}
		if (!mysqli_query($link, sprintf('INSERT INTO %s (%s, %s, %s, %s, %s) VALUES (\'%s\', %s, %s, %s, %s)',
			DB_TABLE_NAME,
			DB_FIELD_DATE,
			DB_FIELD_DOLLAR_BUY,
			DB_FIELD_DOLLAR_SELL,
			DB_FIELD_EURO_BUY,
			DB_FIELD_EURO_SELL,
			mysqli_real_escape_string($link, $date),
			mysqli_real_escape_string($link, $val[BUY]),
			mysqli_real_escape_string($link, $val[SELL]),
			mysqli_real_escape_string($link, $values[EURO][$date][BUY]),
			mysqli_real_escape_string($link, $values[EURO][$date][SELL])
		))) {
			return 4;
		}
	}
	return 0;
}

date_default_timezone_set('America/Argentina/Buenos_Aires');

$dateFrom = strtotime(isset($_GET['dateFrom']) ? $_GET['dateFrom'] : $argv[1]);
$dateTo = strtotime(isset($_GET['dateTo']) ? $_GET['dateTo'] : $argv[2]);

if (!$dateFrom || !$dateTo) {
	header('HTTP/1.1 400 Bad Request', true, 400);
	exit(1);
}

$allValues = array(
	DOLLAR => array(),
	EURO => array(),
);
for ($date = $dateFrom; $date <= $dateTo;) {
	$values = getValues(
		date('Y', $date),
		date('m', $date),
		date('d', $date)
	);
	$allValues[DOLLAR] = array_merge($allValues[DOLLAR], $values[DOLLAR]);
	$allValues[EURO] = array_merge($allValues[EURO], $values[EURO]);

	$maxDate = max(array_keys($allValues[DOLLAR]));
	if (strtotime($maxDate) < $date) {
		$allValues[DOLLAR][date('Y-m-d', $date)] = $allValues[DOLLAR][$maxDate];
		$allValues[EURO][date('Y-m-d', $date)] = $allValues[EURO][$maxDate];
		$date += DAY;
	} else {
		for (;$date <= strtotime($maxDate); $date += DAY) {
			$allValues[DOLLAR][date('Y-m-d', $date)] = $allValues[DOLLAR][$maxDate];
			$allValues[EURO][date('Y-m-d', $date)] = $allValues[EURO][$maxDate];
		}
	}
}

if (DB_HOST !== '') {
	$exit = insertValues($allValues);
	if ($exit !== 0) {
		header('HTTP/1.1 500 Internal Server Error', true, 500);
		exit(2 + $exit);
	}
}
echo json_encode($allValues);