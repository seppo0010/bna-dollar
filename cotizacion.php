<?php
define('BNA_URL', "http://www.bna.com.ar/Cotizador/HistoricoPrincipales?id=billetes&fecha={day}%2F{month}%2F{year}&filtroEuro=1&filtroDolar=1");
// for testing
// define('BNA_URL', "sample{day}{month}{year}.html");
define('DOLLAR', 'Dolar U.S.A');
define('EURO', 'Euro');
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
			if ($valuesDate['Compra'] === 'Compra') {
				continue;
			}
			$retval[$valuesDate['Monedas']][$valuesDate['Fecha']] = array(
				'Compra' => floatval(str_replace(',', '.', $valuesDate['Compra'])),
				'Venta' => floatval(str_replace(',', '.', $valuesDate['Venta'])),
			);
		}
	}
	return $retval;
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

	$newDate = max(array_keys($allValues[DOLLAR]));
	if ($newDate <= $date) {
		$date += DAY;
	} else {
		$date = $newDate + DAY;
	}
}
echo json_encode($allValues);