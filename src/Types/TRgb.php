<?php

namespace Katu\Types;

class TRgb {

	public $value;

	public function __construct($r, $g, $b) {
		if (!self::isValid($value)) {
			throw new \Exception("Invalid string.");
		}

		$this->value = $value;
	}

	static function isValid($value) {
		return is_string($value);
	}

	public function getNumberOfWords() {
		return count(array_filter(explode(' ', $this->value)));
	}

	public function hasAtLeastWords($n) {
		return $this->getNumberOfWords() >= $n;
	}

	public function getForUrl($options = array()) {
		$options = array_merge(array(
			'delimiter' => '-',
			'lowercase' => TRUE,
		), $options);

		return \URLify::filter($this->value, isset($options['maxLength']) ? $options['maxLength'] : NULL, isset($options['language']) ? $options['language'] : NULL);
	}

}
