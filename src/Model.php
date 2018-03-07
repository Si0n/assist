<?php
/**
 * Created by PhpStorm.
 * User: sion
 * Date: 10.02.2018
 * Time: 11:10
 */

namespace assist;

/**
 * Class Model
 * @package assist
 */
class Model {
	private $_attributes = [];

	/**
	 * Model constructor.
	 * @param $properties
	 */
	public function __construct($properties) {
		if (!empty($properties)) {
			foreach ($properties as $key => $value) {
				$this->$key = $value;
			}
		}
	}

	/**
	 * @param $name
	 * @return null
	 */
	public function __get($name) {
		if (property_exists($this, $name)) {
			return $this->$name;
		} else {
			return $this->_attributes[$name] ?? null;
		}
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function __set($name, $value) {
		if (property_exists($this, $name)) {
			$this->$name = $value;
		} else {
			$this->_attributes[$name] = $value;
		}
	}
}