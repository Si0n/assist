<?php
/**
 * Created by PhpStorm.
 * User: siont
 * Date: 10.02.2018
 * Time: 11:19
 */

namespace assist\model;
/**
 * Class DirModel
 * @package assist\model
 */
class DirModel {
	protected $aliases = [];

	public function setAlias($alias, $path) {
		if ($path = $this->createDirectoryPath($path)) {
			$this->aliases[$alias] = $path;
			return true;
		}
		return false;
	}

	public function getAlias($alias) {
		return $this->aliases[$alias] ?? null;
	}

	public function getPath($alias, $localPath) {
		if (empty($aliasPath = $this->getAlias($alias))) Throw new \Exception('Set working_directory first!');
		return "/" . implode("/", array_filter(explode("/", $aliasPath . '/' . $localPath), function ($e) {
				return !empty($e);
			}));
	}

	/**
	 * @param array|string $path :: string $path | array [$alias, $path]
	 * @param $filename
	 * @param $source
	 * @return string
	 * @throws \Exception
	 */
	public function putFile($path, $filename, $source) {
		if (is_array($path)) {
			list($alias, $path) = $path;
			$path = $this->getPath($alias, $path);
		}
		if (!$path) return false;
		if (!is_dir($path)) $path = $this->createDirectoryPath($path);

		$filepath = $this->implodePathFile($path, $filename);
		if (!file_exists($filepath)) {
			file_put_contents($filepath, $source);
			return $filepath;
		} else {
			return $filepath;
		}
	}

	public function implodePathFile($path, $file) {
		return $path . '/' . $file;
	}

	public function createDirectoryPath($path, $rights = 0755) {
		if ($path = array_filter(explode('/', $path), function ($e) {
			return !empty($e);
		})) {
			$carry_path = null;
			foreach ($path as $directory) {
				$carry_path = is_null($carry_path) ? "/$directory" : $carry_path . "/$directory";
				if (!is_dir($carry_path)) {
					mkdir($carry_path, $rights);
				}
			}
			return $carry_path;
		} else {
			Throw new \Exception("Wrong path given!");
		}
	}

	public function deleteFile($path) {
		if (is_file($path)) {
			unlink($path);
		}
	}

	public function removeDirectory($path) {
		if (is_dir($path)) {
			rmdir($path);
		}
	}
}