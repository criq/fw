<?php

namespace Katu;

use \App\Models\File;
use \App\Models\FileAttachment;
use \Sexy\Select;
use \Sexy\OrderBy;
use \Sexy\Keyword;

class Model extends ModelBase {

	protected $__updated = false;

	public function __call($name, $args) {
		// Setter.
		if (preg_match('#^set(?<property>[a-z]+)$#i', $name, $match) && count($args) == 1) {
			$property = $this->getPropertyName($match['property']);
			$value    = $args[0];

			if ($property && $this->update($property, $value)) {
				return true;
			}
		}

		return parent::__call($name, $args);
	}

	static function insert($bindValues = []) {
		$query = static::getPdo()->createQuery();

		$columns = array_map(function($i) {
			return new Pdo\Name($i);
		}, array_keys($bindValues));
		$values  = array_map(function($i) {
			return ':' . $i;
		}, array_keys($bindValues));

		$sql = " INSERT INTO " . static::getTable() . " ( " . implode(", ", $columns) . " ) VALUES ( " . implode(", ", $values) . " ) ";

		$query->setSql($sql);
		$query->setBindValues($bindValues);
		$query->getResult();

		static::change();

		return static::get(static::getPdo()->getLastInsertId());
	}

	static function insertMultiple($items = []) {
		$items = array_values($items);

		$query = static::getPdo()->createQuery();

		$columns = array_map(function($i) {
			return new Pdo\Name($i);
		}, array_keys($items[0]));

		$sql = " INSERT INTO " . static::getTable() . " ( " . implode(", ", $columns) . " ) VALUES ";

		$bindValues = [];
		$sqlRows = [];
		foreach ($items as $row => $values) {
			$sqlRowParams = [];
			foreach ($values as $key => $value) {
				$bindValueKey = implode('_', [
					'row',
					$row,
					$key,
				]);
				$bindValues[$bindValueKey] = $value;
				$sqlRowParams[] = ":" . $bindValueKey;
			}
			$sqlRows[] = " ( " . implode(', ', $sqlRowParams) . " ) ";
		}

		$sql .= implode(", ", $sqlRows);

		$query->setSql($sql);
		$query->setBindValues($bindValues);
		$query->getResult();

		static::change();

		return static::get(static::getPdo()->getLastInsertId());
	}

	static function upsert($bindValues) {
		$object = static::getOneBy($bindValues);
		if (!$object) {
			$object = static::insert($bindValues);
		}

		return $object;
	}

	public function update($property, $value) {
		if (property_exists($this, $property)) {
			if ($this->$property !== $value) {
				$this->$property = $value;
				$this->__updated = true;
			}

			static::change();

			return true;
		}

		return false;
	}

	public function delete() {
		$query = static::getPdo()->createQuery();

		// Delete file attachments.
		if (class_exists('\App\Models\FileAttachment')) {
			foreach ($this->getFileAttachments() as $fileAttachment) {
				$fileAttachment->delete();
			}
		}

		$sql = " DELETE FROM " . static::getTable() . " WHERE " . static::getIdColumnName() . " = :" . static::getIdColumnName();

		$query->setSql($sql);
		$query->setBindValue(static::getIdColumnName(), $this->getId());

		$res = $query->getResult();

		static::change();

		return $res;
	}

	public function save() {
		if ($this->isUpdated()) {

			$columns = static::getTable()->getColumnNames();

			$bindValues = [];
			foreach (get_object_vars($this) as $name => $value) {
				if (in_array($name, $columns) && $name != static::getIdColumnName()) {
					$bindValues[$name] = $value;
				}
			}

			$set = [];
			foreach ($bindValues as $name => $value) {
				$set[] = (new Pdo\Name($name)) . " = :" . $name;
			}

			if ($set) {

				$query = static::getPdo()->createQuery();

				$sql = " UPDATE " . static::getTable() . " SET " . implode(", ", $set) . " WHERE ( " . $this->getIdColumnName() . " = :" . $this->getIdColumnName() . " ) ";

				$query->setSql($sql);
				$query->setBindValues($bindValues);
				$query->setBindValue(static::getIdColumnName(), $this->getId());
				$query->getResult();

			}

			static::change();

			$this->__updated = false;
		}

		return true;
	}

	static function change() {
		static::getTable()->touch();

		return null;
	}

	public function isUpdated() {
		return (bool) $this->__updated;
	}

	static function getAppModels() {
		$dir = BASE_DIR . '/app/Models/';
		$ns = '\\App\\Models';

		$models = [];

		foreach (scandir($dir) as $file) {
			$path = $dir . $file;
			if (is_file($path)) {
				$pathinfo = pathinfo($file);
				$model = $ns . '\\' . $pathinfo['filename'];
				if (class_exists($model)) {
					$models[] = ltrim($model, '\\');
				}
			}
		}

		natsort($models);

		return $models;
	}

	static function getIdColumn() {
		return static::getColumn(static::getIdColumnName());
	}

	static function getIdColumnName() {
		$table = static::getTable();

		return \Katu\Utils\Cache::getRuntime(['databases', $table->pdo->name, 'tables', 'idColumn', $table->name], function() use($table) {
			foreach ($table->pdo->createQuery(" DESCRIBE " . $table)->getResult() as $row) {
				if (isset($row['Key']) && $row['Key'] == 'PRI') {
					return $row['Field'];
				}
			}

			return false;
		});
	}

	public function getId() {
		return $this->{static::getIdColumnName()};
	}

	public function getTransmittableId() {
		return base64_encode(\Katu\Utils\JSON::encodeStandard([
			'class' => $this->getClass(),
			'id'    => $this->getId(),
		]));
	}

	static function getFromTransmittableId($transmittableId) {
		try {
			$array = Utils\JSON::decodeAsArray(base64_decode($transmittableId));
			$class = '\\' . ltrim($array['class'], '\\');

			return $class::get($array['id']);
		} catch (\Exception $e) {
			return false;
		}
	}

	static function get($primaryKey) {
		return static::getOneBy([
			static::getIdColumnName() => $primaryKey,
		]);
	}

	public function exists() {
		return (bool) static::get($this->getId());
	}

	public function setUniqueColumnValue($column, $chars = null, $length = null) {
		if (is_null($chars)) {
			$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		}

		if (is_string($column)) {
			$column = static::getColumn($column);
		}

		if (is_null($length)) {
			$length = $column->getProperties()->length;
		}

		while (true) {
			$string = \Katu\Utils\Random::getFromChars($chars, $length);
			if (!static::getBy([$column->name => $string])->getTotal()) {
				$this->update($column->name, $string);
				$this->save();

				return true;
			}
		}
	}

	public function setUniqueColumnSlug($column, $source, $force = false) {
		// Generate slug.
		$slug = (new \Katu\Types\TString($source))->getForUrl([
			'maxLength' => 245,
		]);

		// If there already is a slug, keep it.
		if (!$force && $this->$column) {
			return true;
		}

		// If it's the same, keep it.
		if (!$force && $slug == $this->$column) {
			return true;
		}

		$preg = '^' . $slug . '(\-([0-9]+))?$';

		// Select all already used slugs.
		$sql = (new \Sexy\Select(static::getColumn($column)))
			->from(static::getTable())
			->where(new \Sexy\CmpNotEq(static::getIdColumn(), $this->getId()))
			->where(new \Sexy\CmpRegexp(static::getColumn($column), $preg))
			;
		$res = static::getPdo()->createQueryFromSql($sql)->getResult();

		// Nothing, keep the slug.
		if (!$res->getCount()) {

			$this->update($column, $slug);

		// There are some, get a new slug.
		} else {

			$suffixes = [];
			foreach ($res->getArray() as $item) {
				preg_match('#' . $preg . '#', $item[$column], $match);
				if (!isset($match[2])) {
					$suffixes[] = 0;
				} else {
					$suffixes[] = (int) $match[2];
				}
			}

			// Sort ascending.
			natsort($suffixes);

			// Find a free suffix;
			$proposedSuffix = 0;
			while (in_array($proposedSuffix, $suffixes)) {
				$proposedSuffix++;
			}

			$this->update($column, implode('-', array_filter([
				$slug,
				$proposedSuffix,
			])));

		}

		$this->save();

		return true;
	}

	static function checkUniqueColumnValue($whereExpressions, $excludeObject = null) {
		$sql = (new \Sexy\Select(static::getTable()))
			->from(static::getTable())
			->addExpressions([
				'where' => $whereExpressions,
			])
			;

		if (!is_null($excludeObject)) {
			$sql->where(new \Sexy\CmpNotEq(static::getIdColumn(), $excludeObject->getId()));
		}

		return !static::createQuery($sql)->getResult()->getTotal();
	}

	public function getFileAttachments($params = [], $expressions = []) {
		$params['objectModel'] = $this->getClass();
		$params['objectId']    = $this->getId();

		if (!isset($expressions['orderBy'])) {
			$expressions['orderBy'] = FileAttachment::getColumn('position');
		}

		return FileAttachment::getBy($params, $expressions);
	}

	public function refreshFileAttachmentPositions() {
		$position = 0;

		// Refresh the ones with position.
		foreach ($this->getFileAttachments([
			new CmpNotEq(FileAttachment::getColumn('position'), 0),
		], [
			'orderBy' => FileAttachment::getColumn('position'),
		]) as $fileAttachment) {
			$fileAttachment->setPosition(++$position);
			$fileAttachment->save();
		}

		// Refresh the ones without position.
		foreach ($this->getFileAttachments([
			new CmpEq(FileAttachment::getColumn('position'), 0),
		], [
			'orderBy' => FileAttachment::getColumn('timeCreated'),
		]) as $fileAttachment) {
			$fileAttachment->setPosition(++$position);
			$fileAttachment->save();
		}

		return true;
	}

	public function getImageFileAttachments($expressions = []) {
		$sql = (new Select(FileAttachment::getTable()))
			->from(FileAttachment::getTable())
			->joinColumns(FileAttachment::getColumn('fileId'), File::getColumn('id'))
			->whereIn(File::getColumn('type'), [
				'image/jpeg',
				'image/png',
				'image/gif',
			])
			->whereEq(FileAttachment::getColumn('objectModel'), (string) $this->getClass())
			->whereEq(FileAttachment::getColumn('objectId'), (int) $this->getId())
			->orderBy([
				new OrderBy(FileAttachment::getColumn('position')),
				new OrderBy(FileAttachment::getColumn('timeCreated'), new Keyword('desc')),
			])
			->addExpressions($expressions)
			;

		return FileAttachment::createQuery($sql)->getResult();
	}

	public function getImageFile() {
		$imageAttachments = $this->getImageFileAttachments();
		if ($imageAttachments->getTotal()) {
			return $imageAttachments[0]->getFile();
		}

		return false;
	}

	public function getImagePath() {
		$file = $this->getImageFile();
		if ($file) {
			return $file->getPath();
		}

		return false;
	}

	public function hasImage() {
		$path = $this->getImagePath();

		return $path && file_exists($path);
	}

}
