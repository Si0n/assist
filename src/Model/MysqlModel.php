<?php
/**
 * Created by PhpStorm.
 * User: sion
 * Date: 10.02.2018
 * Time: 11:30
 */

namespace assist\model;


use assist\Model;

class MysqlModel extends Model {

	protected $db;
	protected $db_errors = [];
	protected $behaviorEvents = [];

	/**
	 * @param string $behavior
	 * @param callable $event
	 */
	public function setBehavior(string $behavior, callable $event) {
		$this->behaviorEvents[$behavior] = $event;
	}

	/**
	 * @param $behavior
	 * @param array ...$arguments
	 * calling trigger callback with $behavior (usually as method name) plus any count of arguments
	 */
	public function trigger($behavior, ...$arguments) {
		if (isset($this->behaviorEvents[$behavior])) call_user_func_array($this->behaviorEvents[$behavior], $arguments);
	}

	/**
	 * @param null $host
	 * @param null $user
	 * @param null $password
	 * @param null $database
	 * @param null $port
	 * @param null $socket
	 * @return $this
	 */
	public function createMysql($host = null, $user = null, $password = null, $database = null, $port = null, $socket = null) {
		try {
			$this->db = new \mysqli($host ?? $this->host, $user ?? $this->user, $password ?? $this->password, $database ?? $this->database, $port ?? $this->port, $socket ?? $this->socket);
			if ($this->db->connect_errno) {
				Throw new \Exception("Cannot connect to MySQL: (" . $this->db->connect_errno . ") " . $this->db->connect_error);
			}
		} catch (\Exception $e) {
			$this->addDbError($e->getMessage());
			echo $e->getMessage();
			die;
		}
		$this->db->set_charset("utf8");
		$this->db->query("SET SQL_MODE = ''");
		$this->trigger(__METHOD__);
		return $this;
	}

	/**
	 * @param string $error
	 */
	public function addDbError(string $error) {
		$this->db_errors[] = $error;
		$this->trigger(__METHOD__, $error);
	}

	/**
	 * @return array
	 */
	public function getDbErrors() {
		return $this->db_errors;
	}

	public function getDB() {
		return $this->db;
	}

	/**
	 * @param array $array
	 * @param string $clue
	 * @return string
	 */
	public function arrayToRequest(array $array, $clue = ',') {
		$request = [];
		foreach ($array as $key => $value) {
			if (empty($value)) continue;
			if (is_array($value)) {
				$request[] = "`{$this->escape($key)}` in ({$this->arrayToArguments($value)})";
			} else {
				$request[] = "`{$this->escape($key)}` = '{$this->escape($value)}'";
			}
		}
		return implode($clue, $request);
	}

	/**
	 * @param array $array
	 * @param string $glue
	 * @param string $wrapper
	 * @return string
	 */
	public function arrayToArguments(array $array, $glue = ',', $wrapper = "'") {
		if (empty($array)) return '';
		return implode($glue, array_reduce($array, function ($carry, $item) use ($wrapper) {
			if (is_null($carry)) $carry = [];
			$carry[] = "{$wrapper}{$this->escape($item)}{$wrapper}";
			return $carry;
		}));
	}

	/**
	 * @param $sql
	 * @return bool|\stdClass
	 * @throws \Exception
	 */
	public function query($sql) {
		$query = $this->db->query($sql);
		if (!$this->db->errno) {
			if ($query instanceof \mysqli_result) {
				$data = array();
				while ($row = $query->fetch_assoc()) {
					$data[] = $row;
				}
				$result = new \stdClass();
				$result->num_rows = $query->num_rows;
				$result->row = isset($data[0]) ? $data[0] : array();
				$result->rows = $data;
				$this->trigger(__METHOD__, $result);
				$query->close();
				return $result;
			} else {
				$this->trigger(__METHOD__);
				return true;
			}
		} else {
			throw new \Exception('Error: ' . $this->db->error . '<br />Error No: ' . $this->db->errno . '<br />' . $sql);
		}
	}

	/**
	 * @param $value
	 * @return mixed
	 */
	public function escape($value) {
		return $this->db->real_escape_string($value);
	}

	/**
	 * @return mixed
	 */
	public function countAffected() {
		return $this->db->affected_rows;
	}

	/**
	 * @return mixed
	 */
	public function getLastId() {
		return $this->db->insert_id;
	}

	/**
	 * @return mixed
	 */
	public function connected() {
		return $this->db->ping();
	}

	public function __destruct() {
		if (!$this->getDbErrors() && $this->db && $this->db->host_info) {
			$this->db->close();
		}
	}
}