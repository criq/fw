<?php

namespace Katu\Tools\Cron;

class Path {

	public $path;

	public function __construct($path) {
		$this->path = $path;
	}

	public function run() {
		return (new \Katu\Types\TURL(Url::joinPaths(Url::getBase(), $this->path)))->ping();
	}

}