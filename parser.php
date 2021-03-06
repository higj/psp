<?php

include 'lib/event.php';
include 'lib/table.php';

$simulation_output = file_get_contents(SIM_OUTPUT_FILE_PATH, FALSE); // Load the simulation output

$events = Event::getEvents($simulation_output); // Fetch all the events (creates an array of Event objects)

// In this script we demonstrate a typical filtering of events
// Here we consider Lambda baryon decay, but the procedure is similar for other particles

// Prepare the Excel table
$table = new Table(['Run', 'φ', 'Δφ', 'κ1', 'Δκ1', 'κ2', 'Δκ2', 'x', 'Δx', 'y', 'Δy', 'z', 'Δz'], 'A10');
// Note #1: vertex coordinate columns (x,dx,y,dy,z,dz) are usually needed for calculating the mean lifetime
// Note #2: Notice the second argument: 'A10'. It refers to the table position (i.e. the table will start from the cell A10)

$good_events_counter = 0;

// Iterate the events
foreach($events as $event) {
	// In most cases Lambda decays into proton and pi-minus (i.e. 2 particles with opposite charges)
	// Therefore let's select the events that actually contain spectrometer data (i.e. produced charged particles)
	if($event->hasFullSpectrometerData()) {
		// We're only interested in events which produced 2 tracks (and also 3, because of a known simulation bug)
		if(($event->number_of_tracks == 3 && $event->number_of_vertices > 1) || ($event->number_of_tracks == 2)) {
			// Let's find the maximal angle between the tracks (necessary for the case of 3 tracks; trivial when there are 2)
			// The class Events has a useful method called maxAngleSpectrometerData() which returns
			// all the useful spectrometer data (such as kappa, errors etc.) corresponding to the highest angle (phi)
			$max_angle_data = $event->maxAngleSpectrometerData();
			
			// Let's save the kappas to separate variables
			$kappa1 = $max_angle_data['tracks'][0]['curvature']['value'];
			$kappa2 = $max_angle_data['tracks'][1]['curvature']['value'];
			
			// Since the decay products are supposed to have opposite charges we expect the kappas to differ in sign
			// Because that's not always the case, let's select the "correct" events
			if(($kappa1 < 0) != ($kappa2 < 0)) {
				$good_events_counter++;
				
				// Add the relevant row in the table (notice that the values are in the same order as the columns from $table definition)
				$table->addTableRow([
						$good_events_counter, // Run
						$max_angle_data['angle']['value'], // Phi
						$max_angle_data['angle']['error'], // Phi error
						$kappa1, // Kappa1
						$max_angle_data['tracks'][0]['curvature']['error'], // Kappa1 Error
						$kappa2, // Kappa2
						$max_angle_data['tracks'][1]['curvature']['error'], // Kappa2 Error
						$max_angle_data['vertex_coord']['x']['value'],
						$max_angle_data['vertex_coord']['x']['error'],
						$max_angle_data['vertex_coord']['y']['value'],
						$max_angle_data['vertex_coord']['y']['error'],
						$max_angle_data['vertex_coord']['z']['value'],
						$max_angle_data['vertex_coord']['z']['error']
				]);
			}
			
		}
	}
}

// Now, create the header for the Excel table with all the relevant information (Optional)
// You can change this information however you like (the format below is recommended due to its clarity)
// Note: A1:H refers to the limits of the header text box 
// The height of this box is adjusted according to the number of rows you have (in this case there are 4 rows)
$table->createHeader('<particle-name>', 'A1:H', [
		'Momentum' => ['value' => 8, 'unit' => 'GeV'], // Edit the values if you injected with a different momentum
		'Total number of injections' => 1000, // Edit the value if the number of injections was different
		'Number of times <particle-name> decayed into <decay-products-name>' => $good_events_counter, 
		'Branching Ratio' => $good_events_counter / 1000
]);

// Save the table
$table->saveTable();
