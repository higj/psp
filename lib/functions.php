<?php

// Implementation of stats_standard_deviation()
function stdev(array $a, $sample = false) {
	$n = count($a);
	if ($n === 0) {
		trigger_error("The array has zero elements", E_USER_WARNING);
		return false;
	}
	if ($sample && $n === 1) {
		trigger_error("The array has only 1 element", E_USER_WARNING);
		return false;
	}
	$mean = array_sum($a) / $n;
	$carry = 0.0;
	foreach ($a as $val) {
		$d = ((double) $val) - $mean;
		$carry += $d * $d;
	};
	if ($sample) {
	   --$n;
	}
	return sqrt($carry / $n);
}

function processCalorimeterCalibration($source) {
	$rows = preg_split("/((\r?\n)|(\r\n?))/", $source); // Fetch the rows
	array_shift($rows);
	
	$united_ph = [];
	
	foreach($rows as $row) {
		$has_matches = preg_match('/(\d{1,})\s+0\s+0\s+0\s+0\s+([0-9]{1,8}([.][0-9]{1,3})?)/', $row, $data);
		
		if($has_matches == 1) {
			$united_ph[$data[1]][] = $data[2];
		}
	}
	
	$result_arr = [];
	
	foreach($united_ph as $momentum => $pulse_heights) {
		$number_of_pulse_heights = count($pulse_heights);
		$average_pulse_height = array_sum($pulse_heights) / $number_of_pulse_heights;
		$avg_pulse_err = stdev($pulse_heights, TRUE) / sqrt($number_of_pulse_heights);
		$energy_err = 0.01 * $momentum;
		
		array_push($result_arr, [
			'H' => $average_pulse_height,
			'dH' => $avg_pulse_err,
			'E' => $momentum,
			'dE' => $energy_err
		]);
	}
	
	return $result_arr;
}

function processSpectrometerCalibration($source) {
	$rows = preg_split("/((\r?\n)|(\r\n?))/", $source); // Fetch the rows
	array_shift($rows);
	
	$united_kappa = [];
	
	
	foreach($rows as $row) {
		$has_matches = preg_match('/(\S+)\t(\S+)\t(\S+)\t(\S+)\t(\S+)\t(\S+)/', $row, $data);
		
		if($has_matches == 1) {
			$united_kappa[$data[1]][] = abs($data[2]);
		}
	}
		
	$result_arr = [];
	
	foreach($united_kappa as $momentum => $kappas) {
		$number_of_kappas = count($kappas);
		$average_kappa = array_sum($kappas) / $number_of_kappas;
		$avg_kappa_err = stdev($kappas, TRUE) / sqrt($number_of_kappas);
		$inverse_momentum = 1/$momentum;
		$inverse_momentum_err = (0.05*$momentum) / pow($momentum, 2);
		
		array_push($result_arr, [
			'1/P' => $inverse_momentum,
			'd(1/P)' => $inverse_momentum_err,
			'k' => $average_kappa,
			'dk' => $avg_kappa_err
		]);
	}
	
	return $result_arr;
		
}

function parseSpectrometer($event, $remove_errors_from_spectrometer_table = TRUE) {
	$tracks_arr = [];
	$vertices_arr = [];
	
	$has_charged_tracks_reconstruction = strpos($event, 'CHARGED TRACKS RECONSTRUCTION');
	$has_charged_vertices_reconstruction = strpos($event, 'CHARGED TRACKS VERTECES RECONSTRUCTION');
	
	if($has_charged_tracks_reconstruction !== FALSE && $has_charged_vertices_reconstruction !== FALSE) {
		$tracks_text = trim(substr(substr($event, $has_charged_tracks_reconstruction), 0, $has_charged_vertices_reconstruction - strlen($event)));
		
		$vertices_text = trim(substr($event, $has_charged_vertices_reconstruction));
		
		preg_match_all('/Track No.   (\d)/', $tracks_text, $tracks_numbers_matches);
		$tracks_numbers_arr = $tracks_numbers_matches[1];
			
		$tracks_text_arr = preg_split('/Track No.   \d/', $tracks_text);
		array_shift($tracks_text_arr);
		
		$vertices_text_arr = preg_split('/Tracks\s+(\d)\sand\s+(\d)/', $vertices_text, NULL, PREG_SPLIT_DELIM_CAPTURE);
		
		array_shift($vertices_text_arr);
		
		foreach($tracks_text_arr as $tracks_text) {
			$track_number = array_shift($tracks_numbers_arr);
			
			$track_params = [];
			
			preg_match_all('/\*\s+(\S+)\s+(?:AKAPPA|TANDIP)  \*  (?:Curvature|Tangent)/', $tracks_text, $fit_params, PREG_UNMATCHED_AS_NULL);
			
			preg_match_all('/\*\s(?:AKAPPA|TANDIP).+(?<=\s)(\S+)(?:\r\n)/', $tracks_text, $param_errors, PREG_UNMATCHED_AS_NULL);
			
			$track_params["curvature"] = ["value" => $fit_params[1][0], "error" => $param_errors[1][0]];
			$track_params["tandip"] = ["value" => $fit_params[1][1], "error" => $param_errors[1][1]];
						
			$tracks_arr[$track_number] = $track_params;
		}
		
		$vertex_track_numbers = [];
		foreach($vertices_text_arr as $vertex_text) {
			if(strlen($vertex_text) == 1) {
				array_push($vertex_track_numbers, intval($vertex_text));
			} else {
				$vertex_params = [];
			
				preg_match_all('/(?<=X|Y|Z)\s+\=\s+(\S+)\s\+\/\-\s+(\S+)\r\n/', $vertex_text, $vertex_coord, PREG_UNMATCHED_AS_NULL);
				
				preg_match('/(?<=Phi=\s{4})(\S+)\s\+\/\-\s+(\S+)(?=\sRad)/', $vertex_text, $azim_angle, PREG_UNMATCHED_AS_NULL);
				
				if(!empty($azim_angle) && !empty($vertex_coord)) {
					$vertex_params["coordinates"] = [
						"x" => ["value" => $vertex_coord[1][0], "error" => $vertex_coord[2][0]], 
						"y" => ["value" => $vertex_coord[1][1], "error" => $vertex_coord[2][1]], 
						"z" => ["value" => $vertex_coord[1][2], "error" => $vertex_coord[2][2]]
					];
					$vertex_params["angle"] = ["value" => $azim_angle[1], "error" => $azim_angle[2]];
				}
				
				
				array_push($vertices_arr, ["tracks" => $vertex_track_numbers, "data" => $vertex_params]);
				
				$vertex_track_numbers = [];
			}

		}
	} else {
		return [
			"track_data" => NULL,
			"vertices_data" => NULL
		];
	}
	
	return [
		"track_data" => $tracks_arr,
		"vertices_data" => $vertices_arr
	];
}

function parseClusters($event, $merge_cluster_data = TRUE, $remove_errors_from_cluster_table = TRUE) {
	$cluster_data = [];
	
	$has_cluster_data = strpos($event, 'ELECTROMAGNETIC CLUSTERS');
	
	if($has_cluster_data !== FALSE) {
		preg_match('/ZWIDTH\r\n(.{1,})(inject|\={2,})/msU',substr($event, $has_cluster_data), $cluster_data); // Cut the text pertaining to the clusters (including column labels, title of the section etc.)

		$clusters_data_list = preg_split("/((\r?\n)|(\r\n?))/", trim($cluster_data[1])); // Extract the lines with the cluster data

		$number_of_clusters = count($clusters_data_list);

		// Sometimes we get only one cluster (either different decay mode or overlap between two clusters), so $clusters_data_list contains only one element. Let's get rid of such cases.

		$cluster_row = []; // We'll populate this array which represents one row containing all the cluster data for one event

				
		foreach($clusters_data_list as $cluster_line) {
			if($remove_errors_from_cluster_table) {
				$cluster_line = preg_replace('/( \+\/\-\d{1,2}\.\d{1,2})/', '', $cluster_line);
			}
			
			$cluster_data_arr = preg_split("/\s+(?!\+)/", trim($cluster_line));

			// Normally $cluster_data_arr should contain 7 numbers (only 4 of them are relevant for our purposes).
			// If the elimination of the unnecessary events (those involving charged products) went correctly in the previous lines then the following condition is redundant (albeit harmless), meaning it would always evaluate to TRUE. But we place it anyway just in case we miss some wrong events (which can potentially alter the $cluster_data_arr length).
			
			if(count($cluster_data_arr) > 4) {
				
				$relevant_cluster_data = array_slice($cluster_data_arr, 1, 4); // Get only the pulse heights and the coordinates
				
				if($merge_cluster_data) {
					$cluster_row = array_merge($cluster_row, $relevant_cluster_data); // If the event has only two clusters then at the end of this foreach loop $cluster_row should have exactly 8 elements
				} else {
					array_push($cluster_row, array_combine(['pulse_height', 'x', 'y', 'z'], $relevant_cluster_data));
				}
				
			} else {
				echo 'This event produced $cluster_data_arr with length <= 4 (Not good)<br />';
			}
		}
	} else {
		return [0, NULL]; // Zero clusters
	}
	
	return [$number_of_clusters, $cluster_row];
}

function getEvents($source, $remove_errors_from_cluster_table = TRUE, $remove_errors_from_spectrometer_table = TRUE) {
	$all_available_particles = array('photon', 'electron', 'positron', 'muon', 'mu-plus', 'neutrino', 'pi-0', 'pi-plus', 'pi-minus', 'k-long', 'k-short', 'k-plus', 'k-minus', 'neutron', 'proton', 'antiproton', 'lambda', 'antilambda', 'sigma-plus', 'sigma-minus', 'sigma-0');
	
	$output = [];
	
	preg_match_all('/(?!GEANT > ' . join($all_available_particles, '\s)(?!GEANT >') . '\s)\d{1,}\.?\d*(?=\r\nGEANT\s>\s\r\n)/', $source, $momentum_values); // Fetch all the momenta of all the injections
		
	$events_arr = preg_split('/GEANT > (' . join($all_available_particles, '|') . ')\s\d{1,}\.?\d*\r\nGEANT > /', $source); // Identify the events in the original output text
	
	$first_event = array_shift($events_arr); // The first element isn't actually an event - just an introductory statement. That's why we would like to remove it from the events array
	
	// Let's iterate the events and categorize them (by their products)
	foreach($events_arr as $event) {
		$value_of_momentum = array_shift($momentum_values[0]);
		
		$event_has_charged_products = (preg_match('(CHARGED TRACKS RECONSTRUCTION|MAGNETIC SPECTROMETER)', $event) === 1); // If the event has "CHARGED TRACKS RECONSTRUCTION" OR "MAGNETIC SPECTROMETER" sections then there are charged products
		
		$event_has_muon_hits = (strpos($event, 'MUON DETECTORR') !== FALSE); // If the event has the title "MUON DETECTORR" then we can say with certainty that there were muon products
		
		$cluster_result_arr = parseClusters($event, FALSE, $remove_errors_from_cluster_table);
		
		$event_data = [
			'injection_momentum' => $value_of_momentum,
			'has_charged_products' => FALSE, 
			'has_muon_detector_hits' => $event_has_muon_hits,
			'cluster_number' => $cluster_result_arr[0], 
			'cluster_data' => $cluster_result_arr[1],
			'spectrometer_data' => []
		];
			
		if($event_has_charged_products == TRUE) {
			$event_data["has_charged_products"] = TRUE;
			$event_data["spectrometer_data"] = parseSpectrometer($event);
		}
		
		array_push($output, $event_data);
		
	}
	
	return $output;
}
