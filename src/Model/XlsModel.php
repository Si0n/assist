<?php
/**
 * Created by PhpStorm.
 * User: sion
 * Date: 10.02.2018
 * Time: 11:42
 */

namespace assist\model;


use assist\Model;

class XlsModel extends Model {

	public $xls;
	public $_activeSheets = [];
	public $_activeSheetPageNumber;
	public $cellDefaultParameters = [
		'width' => 13,
		'styles' => [
			"font" => [
				"size" => 9,
			],
			'alignment' => [
				'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
			],
			'fill' => [
				'type' => \PHPExcel_Style_Fill::FILL_SOLID,
				'color' => ['rgb' => 'd9e1f3']
			],
			'borders' => [
				'outline' => [
					'style' => \PHPExcel_Style_Border::BORDER_THIN,
					'color' => ['rgb' => '000000'],
				],
			],
		]
	];
	public $cellUniqueStyles = [	];

	//init excel
	public function createSheet() {
		$this->xls = new \PHPExcel();
		return $this;
	}

	/**
	 * @param string $title
	 * @param int $page
	 * @return mixed
	 * @method to setup working sheet page
	 */
	public function getSheetPage(string $title = 'New page', int $page = 0) {
		$this->_activeSheetPageNumber = $page;
		if (!isset($this->_activeSheets[$this->_activeSheetPageNumber])) {
			$this->xls->setActiveSheetIndex($this->_activeSheetPageNumber);
			// Получаем активный лист
			$this->_activeSheets[$this->_activeSheetPageNumber] = $this->xls->getActiveSheet();
			// Подписываем лист
			$this->_activeSheets[$this->_activeSheetPageNumber]->setTitle($title);
		}
		return $this->_activeSheets[$this->_activeSheetPageNumber];
	}

	/**
	 * @return mixed
	 * @method to get active Sheet (last page in array), if no sheetPages created - creating page #0 with default title
	 */
	public function getActiveSheet() {
		if (empty($this->_activeSheet) || is_null($this->_activeSheetPageNumber)) {
			return $this->getSheetPage();
		} else {
			return $this->_activeSheets[$this->_activeSheetPageNumber];
		}
	}

	/**
	 * @param $sheet
	 * @param $horizontal
	 * @param $vertical
	 * @param $text
	 * @return mixed
	 */
	public function writeInXLS($sheet, $horizontal, $vertical, $text) {
		$sheet->setCellValueByColumnAndRow($horizontal, $vertical, trim($text));
		return $sheet;
	}

	/**
	 * @param $horizontal
	 * @param $vertical
	 * @param $text
	 * @return mixed
	 */
	public function writeInActiveSheet($horizontal, $vertical, $text) {
		return $this->writeInXLS($this->getActiveSheet(), $horizontal, $vertical, $text);
	}

	public function renderHeadingActiveSheet($heading) {
		$i = 0;
		foreach ($heading as $title => $params) {
			$this->writeInActiveSheet($i, 1, $title);
			$literal_coordinate = $this->getLiteralNumberByCoordinates($i, 1);
			if (!empty($params['styles'])) {
				$this->setStyles($literal_coordinate . ":" . $literal_coordinate, $params['styles']);
			}
			if (!empty($params['width'])) {
				$this->getActiveSheet()->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($i))->setWidth($params['width']);
			}
			$i++;
		}
	}

	private function getStylesForCell($cell_name) {
		$defaultStyle = $this->cellDefaultParameters;
		foreach ($this->cellUniqueStyles as $uniqueStyle) {
			if (in_array($cell_name, $uniqueStyle['columns'])) {
				$defaultStyle['styles'] = array_merge($defaultStyle['styles'], $uniqueStyle['styles']);
			}
		}
		return $defaultStyle['styles'];
		//cellUniqueStyles
	}

	public function renderBodyActiveSheet($data) {
		$vertical = 2;
		foreach ($data as $row_id => $row) {
			$horizontal = 0;
			foreach ($row as $cell_name => $cell) {
				$this->writeInActiveSheet($horizontal, $vertical, $cell);
				$literal_coordinate = $this->getLiteralNumberByCoordinates($horizontal, $vertical);
				$this->setStyles($literal_coordinate . ":" . $literal_coordinate, $this->getStylesForCell($cell_name));
				$horizontal++;
			}
			$vertical++;
		}
	}

	/**
	 * @param $horizontal
	 * @param $vertical
	 * @return string
	 */
	public function getLiteralNumberByCoordinates($horizontal, $vertical) {
		return sprintf("%s%s", $columnLetter = \PHPExcel_Cell::stringFromColumnIndex($horizontal), $vertical);
	}

	public function setStyles($coords, $styles) {
		$this->getActiveSheet()->getStyle($coords)->applyFromArray($styles);
	}

	/**
	 * @param $filename
	 */
	public function renderXLS($filename) {
		header("Expires: Mon, 1 Apr 1974 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");
		header("Content-type: application/vnd.ms-excel");
		header("Content-Disposition: attachment; filename=" . $filename . ".xls");
		// Выводим содержимое файла
		$objWriter = new \PHPExcel_Writer_Excel5($this->xls);
		$objWriter->save('php://output');
	}
}