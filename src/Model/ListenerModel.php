<?php
/**
 * Created by PhpStorm.
 * User: sion
 * Date: 17.02.2018
 * Time: 12:26
 */

namespace assist\Model;


use assist\Model;

class ListenerModel extends Model {
	public $callbacks = [];
	public $index_callback = [];

	/**
	 * @param $param
	 * @param $callback
	 * @param bool $callback_on_error
	 * @return $this
	 */
	public function addListener($param, $callback, $callback_on_error = false) {
		if (is_null($param)) {
			if (is_callable($callback)) {
				$this->index_callback = $callback;
			} elseif (class_exists($callback)) {
				$this->index_callback = new $callback;
			}
		} else {
			if (is_callable($callback) || class_exists($callback)) {
				$this->callbacks[md5(is_array($param) ? json_encode($param) : $param)] = [
					"parameter" => $param,
					"callback" => is_callable($callback) ? $callback : new $callback,
					"on_error" => $callback_on_error
				];
			}
		}
		return $this;
	}

	public function listen() {
		if ($args = $this->detectCLI()) $_GET = $args;
		if (empty($_GET) && $this->index_callback) {
			call_user_func($this->index_callback);
		} else {
			foreach ($this->callbacks as $key => $data) {
				$callback = $this->callbacks[$key]['callback'];
				$callback_error = $this->callbacks[$key]['on_error'];
				if (is_array($data['parameter'])) {
					$parameters = [];
					$is_no_error = true;
					foreach ($data['parameter'] as $parameter) {
						if (!is_array($parameter)) {
							$parameters[$parameter] = $_GET[$parameter] ?? null;
						} else {
							if (isset($parameter['parameter'])) {
								if (isset($parameter['required']) && $parameter['required'] == true) {
									if (isset($_GET[$parameter['parameter']])) {
										$parameters[$parameter['parameter']] = $_GET[$parameter['parameter']];
									} elseif (isset($parameter['default'])) {
										$parameters[$parameter['parameter']] = $parameter['default'];
									} else {
										$parameters[$parameter['parameter']] = null;
										$is_no_error = $is_no_error && false;
									}
								} else {
									if (isset($_GET[$parameter['parameter']])) {
										$parameters[$parameter['parameter']] = $_GET[$parameter['parameter']];
									} elseif (isset($parameter['default'])) {
										$parameters[$parameter['parameter']] = $parameter['default'];
									}
								}
							}
						}
					}
					if ($is_no_error) {
						call_user_func($callback, $parameters);
					}
					if (!$is_no_error && !empty(array_filter($parameters, function ($el) {
							return !is_null($el);
						}))) {
						foreach ($parameters as $param_key => $param_value) {
							if (is_null($param_value)) {
								call_user_func($callback_error, $param_key);
							}
						}
					}
				} else {
					if (isset($_GET[$data['parameter']]) || isset($_POST[$data['parameter']])) {
						call_user_func($callback, isset($_GET[$data['parameter']]) ? $_GET[$data['parameter']] : $_POST[$data['parameter']]);
					} else {
						if ($callback_error) {
							call_user_func($callback_error, $data['parameter']);
						}
					}
				}
			}
		}
	}

	/**
	 * @return array parameters of CLI input
	 */
	protected function detectCLI() {
		$parameters = [];
		if (php_sapi_name() === "cli") {
			array_shift($argv); //ignoring file name
			foreach ($argv as $arg) {
				list($key, $value) = explode("=", $arg);
				if (!empty($key)) {
					$parameters[$key] = $value;
				}
			}
		}
		return $parameters;
	}
}