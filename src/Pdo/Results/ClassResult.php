<?php

namespace Katu\Pdo\Results;

use \PDO;

class ClassResult extends PaginatedResult {

	public function __construct($pdo, $statement, $page, $class) {
		parent::__construct($pdo, $statement, $page);

		$this->class = $class;
	}

	public function setIteratorArray() {
		if (is_null($this->iteratorArray)) {
			$this->iteratorArray = $this->getObjects();
		}
	}

	public function getObjects($class = null) {
		if (!$class && $this->class) {
			$class = $this->class;
		}

		return $this->statement->fetchAll(PDO::FETCH_CLASS, $class);
	}

	public function getOne($class = null) {
		if (!$class && $this->class) {
			$class = $this->class;
		}

		$objects = $this->getObjects();
		if (!isset($objects[0])) {
			return false;
		}

		$object = $objects[0];
		if ($object && method_exists($object, 'save')) {
			$object->save();
		}

		return $object;
	}

	public function getPropertyValues($property) {
		$values = array();

		foreach ($this as $object) {
			$values[] = $object->$property;
		}

		return $values;
	}

}
