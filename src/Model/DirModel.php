<?php
/**
 * Created by PhpStorm.
 * User: sion
 * Date: 10.02.2018
 * Time: 11:19
 */

namespace assist\model;
use assist\Model;

/**
 * Class DirModel
 * @package assist\model
 */
class DirModel extends Model {
	protected $aliases = [];

	/**
	 * @param $alias
	 * @param $path
	 * @return bool
	 * @throws \Exception
	 */
	public function setAlias($alias, $path) {
		if ($path = $this->createDirectoryPath($path)) {
			$this->aliases[$alias] = $path;
			return true;
		}
		return false;
	}

	/**
	 * @param $alias
	 * @return mixed|null
	 */
	public function getAlias($alias) {
		return $this->aliases[$alias] ?? null;
	}

	/**
	 * @param $alias
	 * @param $localPath
	 * @return string
	 * @throws \Exception
	 */
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

	/**
	 * @param $path
	 * @param $file
	 * @return string
	 */
	public function implodePathFile($path, $file) {
		return $path . '/' . $file;
	}

	/**
	 * @param $path
	 * @param int $rights
	 * @return null|string
	 * @throws \Exception
	 */
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

	/**
	 * @param $path
	 */
	public function deleteFile($path) {
		if (is_file($path)) {
			unlink($path);
		}
	}

	/**
	 * @param $path
	 */
	public function removeDirectory($path) {
		if (is_dir($path)) {
			rmdir($path);
		}
	}

	/**
	 * @param $directory
	 * @param array $rules
	 *        $rules = [
	 *            'ignorePath' => (string) actually is a regex mask to exclude some path from the search. Not Required
	 *            'extension' => (array) include only files with such extension. Not Required
	 *        ]
	 * @return \Generator
	 * Recursively goes over given directory and returns all files matched rules
	 */
	public function scan($directory, $rules = []) {
		if (empty($rules['ignorePath']) || !preg_match("/\/{$rules['ignorePath']}\//", $directory)) {
			if (is_dir($directory) && ($handle = opendir($directory))) {
				while (false !== ($entry = readdir($handle))) {
					if ($entry != "." && $entry != "..") {
						$filename = $directory . "/" . $entry;
						if (is_dir($filename)) {
							foreach ($this->scan($filename, $rules) as $file) {
								yield $file;
							}
						} else {
							if (!empty($rules['extension'])) {
								$pathInfo = pathinfo($filename);
								if (!empty($pathInfo['extension']) && in_array($pathInfo['extension'], $rules['extension'])) {
									yield $filename;
								}
							} else {
								yield $filename;
							}
						}
					}
				}
				closedir($handle);
			}
		}
	}
}