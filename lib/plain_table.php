<?php

require_once __DIR__ . '/../config.php';

class PlainTable {
	
	public $column_labels, $rows = [];
	
	public function __construct($table_column_labels) {
		$this->column_labels = implode("\t", $table_column_labels);
	}
	
	/**
	 * Adds a new row to the table
	 * @param array $row is the row (should have the same size as the labels array) 
	 */
	public function addTableRow($row) {
		$this->rows[] = implode("\t", $row);
	}
	
	
	/**
	 * Saves the table to .txt file
	 * @param string $filename is the desired file name
	 */
	public function saveTable($path = PARSED_OUTPUT_FILE_PATH) {
		$output_str = $this->column_labels . PHP_EOL . implode(PHP_EOL, $this->rows);
		
		$fp = fopen($path . '.txt', "wb");
		fwrite($fp, $output_str);
		fclose($fp);
	}
}