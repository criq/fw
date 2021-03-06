<?php

namespace Katu\Pdo;

class DumpDateCollection extends DumpCollection {

	public $datetime;

	public function __construct($datetime) {
		$this->datetime = $datetime;
	}

	public function getMondayDateTime() {
		$datetime = clone $this->datetime;
		$datetime->modify('this week Monday');
		$datetime->setTime(0, 0, 0);

		return $datetime;
	}

	public function getAgeInDays() {
		return (new \Katu\Utils\DateTime)->diff($this->getMondayDateTime())->days;
	}

	public function getAgeInWeeks() {
		return $this->getAgeInDays() / 7;
	}

}
