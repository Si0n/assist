<?php
/**
 * Created by PhpStorm.
 * User: siont
 * Date: 10.02.2018
 * Time: 11:16
 */

namespace assist\model;


use assist\Model;

/**
 * Class CsvModel
 * @package assist\model
 * @property callable|null $callback
 */
class CsvModel extends Model {

	/**
	 * @param null $callback
	 * @return CsvModel $this
	 */
	public function setReadCallback($callback = null) {
		if ($callback && is_callable($callback)) {
			$this->callback = $callback;
		}
		return $this;
	}

	/**
	 * @param $filename
	 * @param null $callback
	 * @return CsvModel $this
	 */
	public function readCSV($filename, $callback = null) {
		$this->setReadCallback($callback);
		$row = 1;
		if ($filename && file_exists($filename) && ($handle = fopen($filename, "r")) !== FALSE) {
			while (($data = fgetcsv($handle)) !== FALSE) {
				$data = array_map(function($value) {
					return trim($value);
				}, $data);
				$num = count($data);
				if ($this->callback) {
					$callback = $this->callback;
					if (is_callable($this->callback)) {
						call_user_func($callback->bindTo($this), $data, $row, $num);
					}
				}
				$row++;
			}
			fclose($handle);
		}
		return $this;
	}
}