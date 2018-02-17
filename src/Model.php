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
	private static $_instance;

	/**
	 * Model constructor.
	 * @param $properties
	 */
	private function __construct($properties) {
		if (!empty($properties)) {
			foreach ($properties as $key => $value) {
				$this->$key = $value;
			}
		}
	}

	/**
	 * @param null $properties
	 * @return Model
	 */
	public static function getInstance($properties = null) {
		if (!self::$_instance) self::$_instance = new self($properties);
		return self::$_instance;
	}

	/**
	 * @param $name
	 * @return null
	 */
	public function __get($name) {
		return $this->$name ?? null;
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function __set($name, $value) {
		$this->$name = $value;
	}

	final private function __clone() {}
}