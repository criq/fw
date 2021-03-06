<?php

namespace Katu\Pdo;

class Column extends \Sexy\Expression {

	public $table;
	public $name;

	public function __construct($table, $name) {
		$this->table = $table;
		$this->name = $name instanceof Name ? $name : new Name($name);
	}

	public function __toString() {
		return $this->getSql();
	}

	public function getSql(&$context = []) {
		return implode('.', [
			$this->table->getSql($context),
			$this->name == '*' ? '*' : $this->name,
		]);
	}

	public function getProperties() {
		return new ColumnProperties($this->table->getColumnDescription($this->name));
	}

	public function getTable() {
		return $this->table;
	}

}
