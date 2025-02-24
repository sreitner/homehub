<?php

/***********************************************
Diagramm Addon
***********************************************/

// Parameter (config/custom.json)
//
// component: diagramm
// ise_id: eine oder mehrere (durch Komma getrennte) ISE_ID des/der zu sammelnden Datenpunkte(s)
// collect: Speicher-Interval. Standard immer.
//  - Ganzzahl: alle <collect> Minuten sammeln
//  - feste Uhrzeit(en) im Format HH:MM[,HH:MM[,...]]
//  - min: Tagesniederstwert, max: Tageshöchstwert, minmax: beides
// history (optional): maximale Anzahl gespeicherter Werte, 1...5000. Standard 200.
// size (optional): Höhe des Diagramms 0...3. Standard 100% Fensterhöhe.
// precision (optional): Anzahl Dezimalstellen bei numerischen Werten. Standard 1.
// only_changed (optional): 1/true/yes: nur speichern, wenn sich der Wert geändert hat. Standard false.
// aufgeklappt (optional): 1/true/yes: Diagramm wird beim laden aufgeklappt. Standard false.
//
////////////////////////////////////////////////////

$colors = array('53a6dc', 'ec7657', 'f3af54', '6fc689', '6a3ba3', 'cc3300', 'ffff00', 'ffffcc', '339933', '999966', 'cc33ff');

if (isset($_GET['lade']) and ($_GET['lade'] == 'content')) {

    $collect = ( isset($_GET['collect']) ? $_GET['collect'] : 0 );

    // history auf ganzzahlige Werte zwischen 1 und 5000 begrenzen, Standard 200
    $history = ( empty($_GET['history']) ? 200 : max(1, min(intval($_GET['history']), 5000)) );

    // Dateiname der cache Datei diagramm_<ise_id>_<collect>_<history>.csv
    $cfile = __DIR__.'/../../cache/diagramm_'.preg_replace('/\D/', '-', $_GET['ise_id']).'_'.preg_replace('/\W/', '-', $collect).'_'.$history.'.csv';

    // Daten zeilenweise in ein Array einlesen
    if (!file_exists($cfile)) die('Cache-Datei '.realpath($cfile).' existiert nicht');
    else $cache = file($cfile);
    if (!is_array($cache)) die('Diagramm hat keine Werte');
	foreach($cache as $linenr => $record) {
		$record = explode(';', rtrim(trim($record), ';'));
		if (count($record) < 2) continue;

		// Prefix zwischenspeichern und entfernen
		$label[$linenr] = $record[0];
		array_shift($record);

/*
		// High und Low berechnen
		$y_min = ( isset($y_min) ? min($y_min, $record) : min($record) );
		$y_max = ( isset($y_max) ? max($y_max, $record) : max($record) );
*/

		// Array transponieren
		foreach ($record as $column => $val) $chart[$column][$linenr] = $val;

		$lines = $linenr;
	}
	unset ($cache);
	#var_dump($chart);

	echo '<canvas id="chart_'.$_GET['modalID'].'" style="position: relative; width: 100vw; height: '.( (isset($_GET['size']) and is_numeric($_GET['size'])) ? strval(30 + 20 * intval($_GET['size'])) : '100' ).'vh"></canvas>';

/*
	$tCh = ($y_min + 0.5) - $y_max;
	if($tCh > 0) { $y_max = $y_max + $tCh; }

	if ($y_min <> 0) $y_min = $y_min -0.5;
	else $y_min = -1;
	if( $y_max == 100) { $y_max = 99.5; }
*/

	if (!empty($_GET['legend'])) $legend = explode(';', $_GET['legend']);

	echo '<script>
ctx = document.getElementById("chart_'.$_GET['modalID'].'");

new Chart(ctx, {
	type: "line",
	data: {
		labels: ["'.implode('","', $label).'"],
		datasets: [
';

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
		}';
		if ($line < $lines) echo ','; echo "\n";
	}

	echo '		]
	},
	options: {
		animation: {
			duration: 0,
		},
		plugins: {
			legend: {
				display: '.( isset($_GET['legend']) ? 'true' : 'false' ).',
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
    $modalId = mt_rand();

    $collect = ( isset($component['collect']) ? $component['collect'] : 0 );

	// history auf ganzzahlige Werte zwischen 1 und 5000 begrenzen, Standard 200
    $history = ( isset($component['history']) ? max(1, min(intval($component['history']), 5000)) : 200 );

	// Dateiname der cache Datei diagramm_<ise_id>_<collect>_<history>.csv
	$cfilelink  = 'cache/diagramm_'.preg_replace('/\D/', '-', $component['ise_id']).'_'.preg_replace('/\W/', '-', $collect).'_'.$history.'.csv';

	$refresh = ( !empty($component["refresh"]) ? 'setInterval(execute_diagramm_'. $modalId.',('.$component['refresh'].'*1000));' : '' );

	$legend = ( !empty($component['legend']) ? '&legend='.$component['legend'] : '' );

    if (!isset($component['size'])) $component['size'] = '';

	//style="display:flow-root;

	//$aufgeklappt = ( (isset($component['aufgeklappt']) and in_array(strtolower($component['aufgeklappt']), array('1', 'yes', 'true'))) ? '$("#'.$modalId.'").collapse("toggle");' : '' );

	if(isset($component['aufgeklappt']) and in_array(strtolower($component['aufgeklappt']), array('1', 'yes', 'true')))
	{
		$aufgeklapptA = "collapse in";
		$aufgeklapptB = "true";
		$aufgeklapptC = "collapse collapsed in";
	}
	else
	{
		$aufgeklapptA = "collapse collapsed";
		$aufgeklapptB = "false";
		$aufgeklapptC = "collapse collapsed";
	}


	if (!isset($component['color'])) $component['color'] = 'transparent';
	if(isset($component['link'])) { $link = '<a href="'.$component['link'].'" target="_blank"><img src="icon/' . $component["icon"] . '" class="icon">' . $component['name'].'</a>'; }
	else { $link = '<img src="icon/' . $component["icon"] . '" class="icon">' . $component['name'];}

    return '<div class="hh" style=\'border-left-color: '.$component['color'].'; border-left-style: solid;\'>'
        . '<div data-toggle="collapse" data-target="#' . $modalId . '" style="display:flow-root;" class="'.$aufgeklapptA.'" aria-expanded="'.$aufgeklapptB.'">'
            . '<a href="'.$cfilelink .'"><img src="icon/' . $component["icon"] . '" class="icon">'.$component['name'].'</a>'
        . '</div>'
        . '<div class="hh2 '.$aufgeklapptA.'" id="'.$modalId.'" aria-expanded="'.$aufgeklapptB.'">'
        .' ...'
        . '</div><div class="clearfix"></div></div>'
    . '
<script type="text/javascript">
$(window).bind("load", execute_diagramm_'. $modalId.');
function execute_diagramm_'. $modalId.'() {
  $.ajax({
    url: "custom/components/diagramm.php?lade=content&modalID='.$modalId.'&ise_id='.$component['ise_id'].'&history='.$history.'&size='.$component['size'].'&collect='.$collect.$legend.'",
    success: function(data) {
	  $("#'. $modalId.'").html("" + data);
	 // '.$aufgeklappt.'

    }
  });
}
</script>
';
}

?>
