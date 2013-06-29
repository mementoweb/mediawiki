<?php
function acquireLinesFromFile($filename) {
	$data = array();

	if (($handle = fopen($filename, 'r')) !== FALSE) {
		while (($filedata = fgetcsv($handle, 1000, ",")) !== FALSE) {
			
			$line = $filedata[0];

			$cur = array (
				$line
			);

			array_push($data, $cur);
		}
	}

	return $data;

}

function acquireCSVDataFromFile($filename, $columns) {
	$data = array();

	if (($handle = fopen($filename, 'r')) !== FALSE) {
		while (($filedata = fgetcsv($handle, 1000, ",")) !== FALSE) {

			$cur = array();

			for ($i = 0; $i < $columns; $i++) {
				$tmp = $filedata[$i];
				array_push($cur, $tmp);
			}

			array_push($data, $cur);

		}
	}

	return $data;
}
?>
