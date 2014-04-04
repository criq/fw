<?php

namespace Jabli;

use \Jabli\FW;

class Route {

	public $pattern;
	public $controller;
	public $method;
	public $conditions;

	public function __construct($pattern, $controller, $method = 'index', $conditions = array()) {
		$this->pattern    = $pattern;
		$this->controller = $controller;
		$this->method     = $method;
		$this->conditions = $conditions;
	}

	static function create($pattern, $controller, $method = 'index', $conditions = array()) {
		return new self($pattern, $controller, $method, $conditions);
	}

	public function getPattern() {
		return rtrim($this->pattern, '/') . '/?';
	}

	public function getCallable() {
		return array("\App\Controllers\\" . $this->controller, $this->method);
	}

	public function isCallable() {
		return is_callable($this->getCallable());
	}

	public function setConditions($conditions = array()) {
		$this->conditions = $conditions;

		return $this;
	}

}