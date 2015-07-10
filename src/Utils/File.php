<?php

namespace Katu\Utils;

class File {

	public $path;

	const TYPE_FILE = 'file';
	const TYPE_DIR  = 'dir';

	public function __construct($path) {
		$this->path = $path;

		return $this;
	}

	public function __toString() {
		return $this->getPath();
	}

	public function getPath() {
		if (file_exists($this->path)) {
			return $this->path;
		}

		$path = FileSystem::joinPaths(BASE_DIR, $this->path);
		if (file_exists($path)) {
			return realpath($path);
		}

		return $this->path;
	}

	public function exists() {
		return file_exists($this->getPath());
	}

	public function getType() {
		if (!$this->exists()) {
			throw new \Katu\Exceptions\FileNotFoundException;
		}

		if (is_file($this->getPath())) {
			return static::TYPE_FILE;
		} elseif (is_dir($this->getPath())) {
			return static::TYPE_DIR;
		}

		return false;
	}

	public function getMime() {
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime = finfo_file($finfo, $this->getPath());
		finfo_close($finfo);

		return $mime;
	}

	public function getPathInfo() {
		return pathinfo($this->getPath());
	}

	public function getExtension() {
		$pathinfo = $this->getPathInfo();

		if (isset($pathinfo['extension'])) {
			return $pathinfo['extension'];
		}

		return false;
	}

	public function isFile() {
		return $this->getType() == static::TYPE_FILE;
	}

	public function isDir() {
		return $this->getType() == static::TYPE_DIR;
	}

	public function isPhpFile() {
		return $this->isFile() && ($this->getMime() == 'text/x-c++' || $this->getExtension() == 'php');
	}

	public function eachRecursive($callback) {
		$iterator = new \RecursiveDirectoryIterator($this, \RecursiveDirectoryIterator::SKIP_DOTS);
		$files = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);

		foreach ($files as $file) {
			call_user_func_array($callback, [new static((string) $file)]);
		}
	}

	public function includeOnce() {
		return include_once $this;
	}

	public function includeAllPhpFiles() {
		return $this->eachRecursive(function($i) {
			if ($i->isPhpFile()) {
				$i->includeOnce();
			}
		});
	}

}