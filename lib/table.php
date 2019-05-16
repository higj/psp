<?php

require_once __DIR__ . '/../config.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Table extends Spreadsheet {
	
	public $column_labels, $position;
	
	public function __construct($table_column_labels, $table_position = 'A1') {
		parent::__construct();
		
		$this->column_labels = $table_column_labels;
		$this->position = $table_position;
		
		$this->createColumnLabels();
	}
	
	/**
	 * Decomposes the cell name into column letter (e.g. "A") and row number (e.g. "1")
	 * @param string $cell is the cell name
	 * @return array
	 */
	public function decomposeCellName($cell) {
		// Decompose $cell into column ("A") and row ("1")
		preg_match('/(?P<column>[A-Z]{1,4})(?P<row>\d{1,})/', $cell, $cell_decomposition);
		array_shift($cell_decomposition);
		return array_intersect_key($cell_decomposition, array_flip(array('column', 'row')));
	}
	
	/**
	 * 
	 * @param array $arr represents the array whose range we calculate
	 * @param string $starting_pos is where the array would be placed in the document
	 * @return string
	 */
	public function getColumnRangeFromArray($arr, $starting_pos = 'A1') {
		$table_last_col_name = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($arr));
		return $starting_pos . ':' . $table_last_col_name . $this->decomposeCellName($starting_pos)['row'];
	}
	
	/**
	 * Creates column labels for the given table from an array of labels
	 */
	private function createColumnLabels() {
		$table_column_labels_style = [
				'font' => [
						'bold' => true,
						'color' => ['rgb' => 'FFFFFF'], // Font color
				],
				'alignment' => [
						'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
				],
				'fill' => [
						'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
						'startColor' => [
								'argb' => '4472c4', // Fill color
						],
				]
		];

		$this->getActiveSheet()->fromArray($this->column_labels, NULL, $this->position);
		$this->getActiveSheet()->getStyle($this->getColumnRangeFromArray($this->column_labels, $this->position))->applyFromArray($table_column_labels_style);
	}
	
	/**
	 * Adds a new row to the table
	 * @param array $value is the row (should have the same size as the labels array) 
	 */
	public function addTableRow($value) {
		$next_empty_row_index = $this->getActiveSheet()->getHighestDataRow() + 1; // Find the next empty row below the left corner of the table for the following push
		
		$this->getActiveSheet()->fromArray($value, NULL, $this->decomposeCellName($this->position)['column'] . $next_empty_row_index);
	}
	
	/**
	 * Creates a header with specified title and data provided by an array
	 * @param string $title is the header title (usually particle name)
	 * @param string $range defines where the header is placed and how wide it should be
	 * @param array $data is the simulation data in the following format: ['Property' => ['value' => <some_value>, 'unit' => <some_unit>], 'Unitless Property' => <some_value>]
	 */
	public function createHeader($title, $range = 'J2:Q', $data = []) {		
		$header_style_arr = [
				'borders' => [
						'outline' => [
								'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
								'color' => ['rgb' => 'FFFF0000'],
						],
				],
				'fill' => [
						'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
						'color' => ['rgb' => 'FFFFFF'],
				],
		];
		
		preg_match('/(\w{1,5}):([a-zA-Z]{1,5})/', $range, $range_decomposition);
		array_shift($range_decomposition);
		
		$corner_cell = $this->decomposeCellName($range_decomposition[0]);
		
		$header_range_arr = ['first_column' => $corner_cell['column'], 'first_row' => $corner_cell['row'], 'last_column' => $range_decomposition[1]];
		
		$title_text = new RichText();
		$title_text->createTextRun($title)->getFont()->setBold(true)->setSize(16);
		$this->getActiveSheet()->getCell($range_decomposition[0])->setValue($title_text);
		
		$this->getActiveSheet()->getStyle($range . ($header_range_arr['first_row'] + count($data)))->applyFromArray($header_style_arr);
		
		$row_counter = intval($header_range_arr['first_row']);
		
		foreach($data as $label => $value) {
			$val = [];
			
			if(!is_array($value)) {
				$val['value'] = strval($value);
			} else {
				if(isset($value['value'])) {
					$val = $value;
				} else {
					$val['value'] = 'none';
				}
			}
			
			$text_obj = new RichText();
			$text_obj->createTextRun($label . ': ')->getFont()->setItalic(true)->setSize(12);
			
			if(isset($val['unit'])) {
				$text_obj->createTextRun($val['value'] . ' [' . strval($val['unit']) . ']')->getFont()->setSize(12);
			} else {
				$text_obj->createTextRun($val['value'])->getFont()->setSize(12);
			}
			
			$row_counter++;
			
			$this->getActiveSheet()->getCell($header_range_arr['first_column'] . $row_counter)->setValue($text_obj);
		}
	}
	
	/**
	 * Saves the table to .xlsx file
	 * @param string $filename is the desired file name
	 */
	public function saveTable($filename = PARSED_OUTPUT_FILE_PATH) {
		$writer = new Xlsx($this);
		$writer->save($filename . '.xlsx');
	}
}