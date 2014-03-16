<?php

namespace Jabli\Utils;

class Datetime extends \DateTime {

	static function get($string = NULL) {
		if (is_int($string)) {
			return new DateTime('@' . $string);
		}

		return new DateTime($string);
	}

	public function getDBDatetimeFormat() {
		return $this->format('Y-m-d H:i:s');
	}

	public function isInTimeout($timeout) {
		return ($this->getTimestamp() + $timeout) >= time();
	}

}