<?php

include 'lib/functions.php';
include 'lib/table.php';

$simulation_output = file_get_contents(SIM_OUTPUT_FILE_PATH, FALSE); // Load the simulation output

$events = getEvents($simulation_output); // Fetch all the events

$table = new Table(['Run' , 'φ', 'Δφ', 'κ1', 'Δκ1', 'κ2', 'Δκ2', 'x', 'Δx', 'y', 'Δy', 'z', 'Δz'], 'A10'); // Prepare the Excel table

// Here you should filter the relevant events and insert the appropriate row into the Excel table

$good_events_counter = 0;

foreach($events as $event) {
	if($event['has_charged_products'] == TRUE 
	&& !empty($event['spectrometer_data'])
	&& !empty($event['spectrometer_data']['track_data'])
	&& !empty($event['spectrometer_data']['vertices_data'])
	) {
		$number_of_tracks = sizeof($event['spectrometer_data']['track_data']);
		$number_of_vertices = sizeof($event['spectrometer_data']['vertices_data']);
		
		if($number_of_tracks == 3 && $number_of_vertices == 3) {
			$vertex1 = $event['spectrometer_data']['vertices_data'][0]['data'];
			$vertex2 = $event['spectrometer_data']['vertices_data'][1]['data'];
			$vertex3 = $event['spectrometer_data']['vertices_data'][2]['data'];
			
			if(!empty($vertex1) && !empty($vertex2) && !empty($vertex3)) {
				$three_angles = [
					$vertex1['angle']['value'],
					$vertex2['angle']['value'],
					$vertex3['angle']['value']
				];
								
				$three_decay_coordinates = [
					$vertex1['coordinates']['x']['value'],
					$vertex2['coordinates']['x']['value'],
					$vertex3['coordinates']['x']['value']		
				];
				
				
				$real_phi_angle_value = max($three_angles);
				$real_phi_angle_index = array_keys($three_angles, $real_phi_angle_value)[0];
				$real_phi_angle_error = $event['spectrometer_data']['vertices_data'][$real_phi_angle_index]['data']['angle']['error'];
				$tracks_needed = $event['spectrometer_data']['vertices_data'][$real_phi_angle_index]['tracks'];
				$track_to_ignore = min(array_diff(range(1, 10), $tracks_needed));
				
				$chosen_x_index = $real_phi_angle_index; // We choose the x coordinate corresponding to the phi angle
								
				$relevant_coord_arr = $event['spectrometer_data']['vertices_data'][$chosen_x_index]['data']['coordinates'];
				
				$x_coord = $relevant_coord_arr['x']['value']; // Remove if $x_coord should be defined somehow else
				$x_coord_err = $relevant_coord_arr['x']['error'];
				$y_coord = $relevant_coord_arr['y']['value'];
				$y_coord_err = $relevant_coord_arr['y']['error'];
				$z_coord = $relevant_coord_arr['z']['value'];
				$z_coord_err = $relevant_coord_arr['z']['error'];
				
				$kappa1 = $event['spectrometer_data']['track_data'][$tracks_needed[0]]['curvature']['value'];
				$kappa2 = $event['spectrometer_data']['track_data'][$tracks_needed[1]]['curvature']['value'];
				
				if(($kappa1 < 0) != ($kappa2 < 0)) {
					$good_events_counter++;
				
					$table->addTableRow([
						$good_events_counter, // Run
						$real_phi_angle_value, // Phi
						$real_phi_angle_error, // Phi error
						$kappa1, // Kappa1
						$event['spectrometer_data']['track_data'][$tracks_needed[0]]['curvature']['error'], // Kappa1 Error
						$kappa2, // Kappa2
						$event['spectrometer_data']['track_data'][$tracks_needed[1]]['curvature']['error'], // Kappa2 Error
						$x_coord,
						$x_coord_err,
						$y_coord,
						$y_coord_err,
						$z_coord,
						$z_coord_err
					]);	
				}
			}
		} elseif($number_of_tracks == 2 && $number_of_vertices == 1) {
			
			
			$tracks_arr = $event['spectrometer_data']['track_data'];
			$keys = array_keys($tracks_arr);
			
			$kappa1 = $tracks_arr[$keys[0]]['curvature']['value'];
			$kappa2 = $tracks_arr[$keys[1]]['curvature']['value'];
			
			if(($kappa1 < 0) != ($kappa2 < 0)) {
				$good_events_counter++;
				
				$table->addTableRow([
					$good_events_counter, // Run
					$event['spectrometer_data']['vertices_data'][0]['data']['angle']['value'], // Phi
					$event['spectrometer_data']['vertices_data'][0]['data']['angle']['error'], // Phi error
					$kappa1, // Kappa1
					$tracks_arr[$keys[0]]['curvature']['error'], // Kappa1 Error
					$kappa2, // Kappa2
					$tracks_arr[$keys[1]]['curvature']['error'], // Kappa2 Error
					$event['spectrometer_data']['vertices_data'][0]['data']['coordinates']['x']['value'],
					$event['spectrometer_data']['vertices_data'][0]['data']['coordinates']['x']['error'],
					$event['spectrometer_data']['vertices_data'][0]['data']['coordinates']['y']['value'],
					$event['spectrometer_data']['vertices_data'][0]['data']['coordinates']['y']['error'],
					$event['spectrometer_data']['vertices_data'][0]['data']['coordinates']['z']['value'],
					$event['spectrometer_data']['vertices_data'][0]['data']['coordinates']['z']['error'],
					'Two tracks'
				]);
			}
		}
	}
}


// Now, create the header for the Excel table with all the relevant information (Optional)
$table->createHeader('Lambda', 'A1:H', [
		'Momentum' => 8,
		'Total number of injections' => 1000,
		'Number of times Pi-0 decayed into 2 photons' => $good_events_counter,
		'Branching Ratio' => $good_events_counter / 1000
]);

// Save the table
$table->saveTable(XLSX_FILENAME);