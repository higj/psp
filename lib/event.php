<?php

require_once __DIR__ . '/../config.php';

class Event {
	
	public $injection_momentum, $spectrometer_data = [], $cluster_number, $cluster_data, $has_charged_products = FALSE, $has_muon_hits = FALSE;
	
	public $number_of_tracks = 0, $number_of_vertices = 0;
	
	public function __construct($momentum, $event) {
			
		$this->injection_momentum = $momentum;
		
		$cluster_result_arr = $this->parseClusters($event);
		$this->cluster_number = $cluster_result_arr[0];
		$this->cluster_data = $cluster_result_arr[1];
		
		// If the event has "CHARGED TRACKS RECONSTRUCTION" OR "MAGNETIC SPECTROMETER" sections then there are charged products
		if(preg_match('(CHARGED TRACKS RECONSTRUCTION|MAGNETIC SPECTROMETER)', $event) === 1) {
			$this->has_charged_products = TRUE;
			$this->spectrometer_data = $this->parseSpectrometer($event);
			
			$this->number_of_tracks = count($this->spectrometer_data['track_data']);
			$this->number_of_vertices = count($this->spectrometer_data['vertices_data']);
		}
		
		$this->has_muon_hits = (strpos($event, 'MUON DETECTORR') !== FALSE); // If the event has the title "MUON DETECTORR" then we can say with certainty that there were muon products
	}
	
	private function parseSpectrometer($event, $remove_errors_from_spectrometer_table = TRUE) {
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
					"track_data" => [],
					"vertices_data" => []
			];
		}
		
		return [
				"track_data" => $tracks_arr,
				"vertices_data" => $vertices_arr
		];
	}
	
	
	private function parseClusters($event, $merge_cluster_data = FALSE, $remove_errors_from_cluster_table = TRUE) {
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
	
	
	public static function getEvents($source, $remove_errors_from_cluster_table = TRUE, $remove_errors_from_spectrometer_table = TRUE) {
		$all_available_particles = array('photon', 'electron', 'positron', 'muon', 'mu-plus', 'neutrino', 'pi-0', 'pi-plus', 'pi-minus', 'k-long', 'k-short', 'k-plus', 'k-minus', 'neutron', 'proton', 'antiproton', 'lambda', 'antilambda', 'sigma-plus', 'sigma-minus', 'sigma-0');
		
		$output = [];
		
		preg_match_all('/(?!GEANT > ' . join($all_available_particles, '\s)(?!GEANT >') . '\s)\d{1,}\.?\d*(?=\r\nGEANT\s>\s\r\n)/', $source, $momentum_values); // Fetch all the momenta of all the injections
		
		$events_arr = preg_split('/GEANT > (' . join($all_available_particles, '|') . ')\s\d{1,}\.?\d*\r\nGEANT > /', $source); // Identify the events in the original output text
		
		$first_event = array_shift($events_arr); // The first element isn't actually an event - just an introductory statement. That's why we would like to remove it from the events array
		
		foreach($events_arr as $event) {
			$value_of_momentum = array_shift($momentum_values[0]);
			
			$output[] = new self($value_of_momentum, $event);
		}
		
		return $output;
	}
	
	/**
	 * Checks if the event has charged products and all the track & vertices data
	 * @return boolean
	 */
	public function hasFullSpectrometerData() {
		return !$this->emptyArr($this->spectrometer_data);
	}
	
	/**
	 * Finds the 2 tracks corresponding to the highest angle (phi) and returns their data
	 * @return array of relevant track data
	 */
	public function maxAngleSpectrometerData() {
		$max_angle_index = 0;
		$max_angle = 0;
		$max_angle_error = 0;
				
		foreach($this->spectrometer_data['vertices_data'] as $v_index => $vertex) {
			if(abs($vertex['data']['angle']['value']) > abs($max_angle)) {
				$max_angle = $vertex['data']['angle']['value'];
				$max_angle_index = $v_index;
				$max_angle_error = $vertex['data']['angle']['error'];
			}
		}
				
		return array_merge(['angle' => ['index' => $max_angle_index, 'value' => $max_angle, 'error' => $max_angle_error]], $this->spectrometerDataFromAngle($max_angle_index));
	}
	
	/**
	 * Returns spectrometer data for the specific angle (i.e. track data and vertex coordinates corresponding to that angle)
	 * @param int angle index
	 * @return array with relevant vertex and track data
	 */
	public function spectrometerDataFromAngle($angle_index) {
		$track_numbers = $this->spectrometer_data['vertices_data'][$angle_index]['tracks'];
		
		return ['vertex_coord' => $this->spectrometer_data['vertices_data'][$angle_index]['data']['coordinates'],
				'tracks' => [
						array_merge(['track_no' => $track_numbers[0]], $this->spectrometer_data['track_data'][$track_numbers[0]]),
						array_merge(['track_no' => $track_numbers[1]], $this->spectrometer_data['track_data'][$track_numbers[1]])
				]
		];
	}
	
	/**
	 * Check if the multidimensional array is empty
	 * @param array $value
	 * @return boolean
	 */
	private function emptyArr($value)
	{
		if (is_array($value)) {
			$empty = TRUE;
			array_walk_recursive($value, function($item) use (&$empty) {
				$empty = $empty && empty($item);
			});
		} else {
			$empty = empty($value);
		}
		return $empty;
	}
}