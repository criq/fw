<?php

namespace Katu\Pdo;

class DumpDay extends DumpDateCollection {

	public $datetime;

	public function getObsoleteDumps() {
		// Older than a month, keep newest backup from every day.
		if ($this->getAgeInWeeks() > 2) {

			$this->sortDumpsByTime();

			return new DumpCollection(array_slice($this->dumps, 0, -1));

		// Keep all.
		} else {

			return new DumpCollection;

		}
	}

}
