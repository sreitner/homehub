<?php

/***********************************************
Diagramm Addon
***********************************************/

// Parameter (config/custom.json)
//
// component: diagramm
// ise_id: eine oder mehrere (durch Komma getrennte) ISE_ID des/der zu sammelnden Datenpunkte(s)
// collect: Speicher-Interval. Standard immer.
//	- Ganzzahl: alle <collect> Minuten sammeln
//	- feste Uhrzeit(en) im Format HH:MM[,HH:MM[,...]]
//	- min: Tagesniederstwert, max: Tageshöchstwert, minmax: beides
// history (optional): maximale Anzahl gespeicherter Werte, 1...5000. Standard 200.
// size (optional): Höhe des Diagramms 0...3. Standard 100% Fensterhöhe.
// precision (optional): Anzahl Dezimalstellen bei numerischen Werten. Standard 1.
// only_changed (optional): 1/true/yes: nur speichern, wenn sich der Wert geändert hat. Standard false.
// aufgeklappt (optional): 1/true/yes: Diagramm wird beim laden aufgeklappt. Standard false.
//
////////////////////////////////////////////////////

$colors = array('53a6dc', 'ec7657', 'f3af54', '6fc689', '6a3ba3', 'cc3300', 'ffff00', 'ffffcc', '339933', '999966', 'cc33ff');

if (!empty($_GET['diagramm'])) {

	// Diagramm-Parameter
	if (!$param = json_decode(base64_decode($_GET['diagramm']), true)) die('Fehlerhafte Daten');
	if (!$chart_id = preg_replace('/[^\d\w\-_]/i', '', $param['chart'])) die('Ungültiger Diagrammlink');
	$modal_id = rtrim(base64_encode($chart_id), '=');
	$legend = ( !empty($param['legend']) ? preg_split("/[\t,;]/i", $param['legend']) : array() );

	// Dateiname der cache Datei diagramm_<ise_id>_<collect>_<history>.csv
	$cfile = realpath(__DIR__.'/../../cache').'/diagramm_'.$chart_id.'.csv';
	if (!file_exists($cfile)) die('Cache-Datei '.$cfile.' existiert nicht');

	// Daten zeilenweise in ein Array einlesen
	$cache = file($cfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if (!is_array($cache)) die('Diagramm hat keine Werte');
	foreach($cache as $linenr => $record) {
		$record = explode(';', rtrim(trim($record), ';'));

		// Zeilen ohne Wert(e) überspringen
		if (count($record) < 2) continue;

		// Prefix zwischenspeichern und entfernen
		$label[$linenr] = $record[0];
		array_shift($record);

/*
		// High und Low berechnen
		$y_min = ( isset($y_min) ? min($y_min, $record) : min($record) );
		$y_max = ( isset($y_max) ? max($y_max, $record) : max($record) );
*/

		// Array transponieren. Erzeugt pro Datenpunkt ein Array mit den Werten.
		foreach ($record as $column => $val) $chart[$column][$linenr] = $val;
	}

	// Puffer freigeben
	unset ($cache);

	echo '<canvas id="chart_'.$modal_id.'" style="position: relative; width: 100vw; height: '.( (isset($param['size']) and is_numeric($param['size'])) ? strval(30 + 20 * intval($param['size'])) : '100' ).'vh"></canvas>'.PHP_EOL;

/*
	$tCh = ($y_min + 0.5) - $y_max;
	if($tCh > 0) { $y_max = $y_max + $tCh; }

	if ($y_min <> 0) $y_min = $y_min -0.5;
	else $y_min = -1;
	if( $y_max == 100) { $y_max = 99.5; }
*/

	echo '
<script>
ctx = document.getElementById("chart_'.$modal_id.'");

new Chart(ctx, {
	type: "line",
	data: {
		labels: ["'.implode('","', $label).'"],
		datasets: [
';

	// Datenreihen schreiben
	foreach ($chart as $line => $values) {
		echo '		{
			label: "'.( isset($legend[$line]) ? $legend[$line] : '' ).'",
			data: ['.implode(',', $values).'],
			borderColor: "#'.$colors[($line % count($colors))].'",
			borderWidth: 1.5,
			pointRadius: 0,
			fill: false,
			backgroundColor: "transparent",
			lineTension: 0.1,
		},
';
	}

	// Diagramm-Parameter
	echo '		]
	},
	options: {
		animation: {
			duration: 0,
		},
		plugins: {
			legend: {
				display: '.( count($legend) ? 'true' : 'false' ).',
			},
		},
		scales: {
			x: {
				ticks: {
					maxTicksLimit: 11,
					minRotation: 0,
					labelOffset: 0,
					sampleSize: 20,
					color: "white",
				},
				grid: {
					display: true,
					drawOnChartArea: false,
					drawTicks: true,
				},
			},
			y: {
				ticks: {
					precision: 0,
					color: "white",
				},
				grid: {
					display: true,
					drawOnChartArea: true,
					drawTicks: false,
					color: "grey",
				}
			},
		}
	}
});
</script>
';

	exit();
}

function diagramm($component) {

	$collect = ( isset($component['collect']) ? $component['collect'] : 0 );

	// history auf ganzzahlige Werte zwischen 1 und 5000 begrenzen, Standard 200
	$history = ( isset($component['history']) ? max(1, min(intval($component['history']), 5000)) : 200 );

	// Dateiname der cache Datei diagramm_<ise_id>_<collect>_<history>.csv
	$chart_id = preg_replace('/\D/', '-', $component['ise_id']).'_'.preg_replace('/\W/', '-', $collect).'_'.$history;
	$cfilelink	= 'cache/diagramm_'.$chart_id.'.csv';

	// dom Diagramm-ID
	$modal_id = rtrim(base64_encode($chart_id), '=');

	#$refresh = ( !empty($component["refresh"]) ? 'setInterval(execute_diagramm_'. $modal_id.',('.$component['refresh'].'*1000));' : '' );

	// Parameter formatieren und zusammenfassen
	$param = array('chart' => $chart_id);
	if (isset($component['size'])) $param['size'] = $component['size'];
	if (isset($component['legend'])) $param['legend'] = $component['legend'];

	//style="display:flow-root;

	//$aufgeklappt = ( (isset($component['aufgeklappt']) and in_array(strtolower($component['aufgeklappt']), array('1', 'yes', 'true'))) ? '$("#'.$modal_id.'").collapse("toggle");' : '' );

	if(isset($component['aufgeklappt']) and in_array(strtolower($component['aufgeklappt']), array('1', 'yes', 'true'))) {
		$aufgeklappt = array('collapse in', 'true', 'collapse collapsed in');
	} else {
		$aufgeklappt = array('collapse collapsed', 'false', 'collapse collapsed');
	}

	if (!isset($component['color'])) $component['color'] = 'transparent';
	if (isset($component['link'])) { $link = '<a href="'.$component['link'].'" target="_blank"><img src="icon/' . $component["icon"] . '" class="icon">' . $component['name'].'</a>'; }
	else { $link = '<img src="icon/' . $component["icon"] . '" class="icon">' . $component['name'];}

	return '<div class="hh" style=\'border-left-color: '.$component['color'].'; border-left-style: solid;\'>'
		. '<div data-toggle="collapse" data-target="#' . $modal_id . '" style="display:flow-root;" class="'.$aufgeklappt[0].'" aria-expanded="'.$aufgeklappt[1].'">'
			. '<a href="'.$cfilelink .'"><img src="icon/' . $component["icon"] . '" class="icon">'.$component['name'].'</a>'
		. '</div>'
		. '<div class="hh2 '.$aufgeklappt[0].'" id="'.$modal_id.'" aria-expanded="'.$aufgeklappt[1].'">'
		.' ...'
		. '</div><div class="clearfix"></div></div>'
	. '
<script type="text/javascript">
$(window).bind("load", execute_diagramm_'. $modal_id.');
function execute_diagramm_'. $modal_id.'() {
  $.ajax({
	url: "custom/components/diagramm.php?diagramm='.base64_encode(json_encode($param)).'",
	success: function(data) {
	  $("#'. $modal_id.'").html("" + data);

	}
  });
}
</script>
';
}

?>
