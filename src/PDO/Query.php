<?php

namespace Katu\PDO;

class Query
{
	protected $connection;
	protected $factory;
	protected $page;
	protected $params = [];
	protected $sql;

	public function __construct(Connection $connection, $sql, ?array $params = [])
	{
		$this->setConnection($connection);
		$this->setParams($params);
		$this->setSql($sql);
	}

	public function setConnection(Connection $connection) : Query
	{
		$this->connection = $connection;

		return $this;
	}

	public function getConnection() : Connection
	{
		return $this->connection;
	}

	public function setSql($sql) : Query
	{
		$this->sql = $sql;
		if ($sql instanceof \Sexy\Select && $sql->getPage()) {
			$this->setPage($sql->getPage());
		}

		return $this;
	}

	public function getSql()
	{
		return $this->sql;
	}

	public function setParam(string $name, $value) : Query
	{
		$this->params[$name] = $value;

		return $this;
	}

	public function setParams(?array $params = []) : Query
	{
		$this->params = array_merge($this->params, $params);

		return $this;
	}

	public function getParams() : array
	{
		return $this->params;
	}

	public function setPage(\Sexy\Page $page) : Query
	{
		$this->page = $page;

		return $this;
	}

	public function getPage() : ?\Sexy\Page
	{
		return $this->page;
	}

	public function setFactory(\Katu\Interfaces\Factory $factory) : Query
	{
		$this->factory = $factory;

		return $this;
	}

	public function getFactory() : ?\Katu\Interfaces\Factory
	{
		return $this->factory;
	}

	public function getStatement()
	{
		$statement = $this->getConnection()->getConnection()->prepare($this->getSql());

		foreach ($this->getParams() as $name => $value) {
			if (is_string($value)) {
				$statement->bindValue($name, $value, \PDO::PARAM_STR);
			} elseif (is_int($value)) {
				$statement->bindValue($name, $value, \PDO::PARAM_INT);
			} elseif (is_float($value)) {
				$statement->bindValue($name, $value, \PDO::PARAM_STR);
			} else {
				$statement->bindValue($name, $value, \PDO::PARAM_STR);
			}
		}

		return $statement;
	}

	public function getResult()
	{
		return Results\Result::createFromQuery($this);
	}
}
