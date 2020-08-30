<?php

namespace Katu\PDO;

class Column extends \Sexy\Expression
{
	public $name;
	public $table;

	public function __construct(TableBase $table, Name $name)
	{
		$this->table = $table;
		$this->name = $name;
	}

	public function __toString()
	{
		return $this->getSql();
	}

	public function getName()
	{
		return $this->name;
	}

	public function getSql(&$context = [])
	{
		return implode('.', [
			$this->table->getSql($context),
			$this->name == '*' ? '*' : $this->name,
		]);
	}

	public function getProperties()
	{
		return new ColumnProperties($this->table->getColumnDescription($this->name));
	}

	public function getTable()
	{
		return $this->table;
	}
}