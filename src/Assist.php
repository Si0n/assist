<?php
/**
 * Created by PhpStorm.
 * User: sion
 * Date: 10.02.2018
 * Time: 11:46
 */

namespace assist;


class Assist {

	private static $modelNamespaces = ['\\assist\\model\\'];
	protected static $models = [];


	/***
	 * @param $name
	 * @param null $properties
	 * @return mixed|null
	 */
	public static function model($name, $properties = null) {
		if (!isset(self::$models[$name])) {
			foreach (self::$modelNamespaces as $modelNamespace) {
				$c = $modelNamespace . $name;
				/** @var Model $c */
				if (class_exists($c)) {
					self::$models[$name] = $c::getInstance($properties);
					break;
				}
			}
		}
		return self::$models[$name] ?? null;
	}

	public static function addModelNamespace($namespace) {
		self::$modelNamespaces[] = $namespace;
	}
}