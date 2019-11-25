<?php

namespace Katu\Models;

class View extends Base {

	static $_cache              = true;
	static $_cacheTimeout       = 86400;
	static $_cacheOnUpdate      = true;
	static $_cacheAdvance       = .75;
	static $_materialize        = false;
	static $_materializeTimeout = 86400;
	static $_materializeAdvance = 1;
	static $_materializeHours   = [];
	static $_autoIndices        = true;
	static $_compositeIndex     = true;
	static $_customIndices      = [];

	static function getTable() {
		static::cacheIfExpired();

		return static::isCached() ? static::getCachedTable() : static::getView();
	}

	static function getTableName() {
		return static::isCached() ? static::getCachedTableName() : static::getViewName();
	}

	static function getView() {
		return new \Katu\PDO\View(static::getConnection(), static::getViewName());
	}

	static function getViewName() {
		return new \Katu\PDO\Name(static::TABLE);
	}

	static function getColumn($name, $options = []) {
		if (isset($options['cache']) && $options['cache'] === false) {
			$table = static::getView();
		} else {
			$table = static::getTable();
		}

		return new \Katu\PDO\Column($table, new \Katu\PDO\Name($name));
	}

	static function getViewColumn($name, $options = []) {
		$options['cache'] = false;

		return static::getColumn($name, $options);
	}

	static function getCachedTable() {
		return new \Katu\PDO\Table(static::getConnection(), static::getCachedTableName());
	}

	static function getCachedTableName() {
		$name = implode('_', [
			'_cache',
			static::getViewName()->getName(),
		]);

		if (strlen($name) > 64) {
			return substr($name, 0, 60) . substr(sha1($name), 0, 4);
		}

		return new \Katu\PDO\Name($name);
	}

	static function getCachedTableCacheName() {
		return ['!databases', '!' . static::getView()->getConnection()->name, '!views', '!cachedView', '!' . static::TABLE];
	}

	static function isCached() {
		return static::$_cache;
	}

	static function isMaterialized() {
		return static::$_materialize;
	}

	static function cachedTableExists() {
		return in_array(static::getCachedTableName()->getName(), static::getConnection()->getTableNames());
	}

	static function materializedTableExists() {
		return in_array(static::getMaterializedTableName(), static::getConnection()->getTableNames());
	}

	static function cacheHasUpdatedTables() {
		if (static::$_cacheOnUpdate) {

			$sourceTables = static::getView()->getSourceTables();
			foreach ($sourceTables as $sourceTable) {

				if (!$sourceTable->exists()) {
					continue;
				}

				$lastUpdatedTime = $sourceTable->getLastUpdatedTime();
				if (!is_null($lastUpdatedTime) && $lastUpdatedTime > static::getLastCachedTime()) {
					return true;
				}

			}

		}

		return false;
	}

	static function getCacheAge() {
		return time() - static::getLastCachedTime();
	}

	static function getMaterializeAge() {
		return time() - static::getLastMaterializedTime();
	}

	static function getCacheExpiryRatio() {
		return static::getCacheAge() / static::$_cacheTimeout;
	}

	static function getMaterializeExpiryRatio() {
		return static::getMaterializeAge() / static::$_materializeTimeout;
	}

	static function isCacheExpired($expiryRatio = 1) {
		if (!static::isCached()) {
			return false;
		}

		if (!static::cachedTableExists()) {
			return true;
		}

		if (static::getCacheExpiryRatio() >= $expiryRatio) {
			return true;
		}

		if (static::cacheHasUpdatedTables()) {
			return true;
		}

		return false;
	}

	static function isCacheExpiredAdvance() {
		return static::isCacheExpired(static::$_cacheAdvance);
	}

	static function isMaterializeExpired($expiryRatio = 1) {
		if (!static::isMaterialized()) {
			return false;
		}

		if (!static::materializedTableExists()) {
			return true;
		}

		if (static::getMaterializeExpiryRatio() >= $expiryRatio) {
			return true;
		}

		return false;
	}

	static function isMaterializeExpiredAdvance($expiryRatio = 1) {
		return static::isMaterializeExpired(static::$_materializeAdvance);
	}

	static function isMaterializable() {
		if (!static::$_materializeHours || \Katu\Env::getPlatform() == 'dev') {
			return true;
		}

		return in_array((int) (new \Katu\Tools\DateTime\DateTime)->format('h'), static::$_materializeHours);
	}

	static function resetCache() {
		return \Katu\Utils\Cache::reset(static::getCachedTableCacheName());
	}

	static function getMaterializedTable() {
		return new \Katu\PDO\Table(static::getConnection(), static::getMaterializedTableName());
	}

	static function getMaterializedTableName() {
		return implode('_', [
			'mv',
			preg_replace('#^view_#', null, static::getViewName()->getName()),
		]);
	}

	static function copy($sourceTable, $destinationTable) {
		@set_time_limit(600);

		// Get a temporary table.
		$temporaryTableName = new \Katu\PDO\Name('_tmp_' . strtoupper(\Katu\Tools\Random\Generator::getIdString(8)));
		$temporaryTable = new \Katu\PDO\Table($destinationTable->getConnection(), $temporaryTableName);

		// Copy into temporary table view.
		$params = [
			'disableNull'    => true,
			'autoIndices'    => static::$_autoIndices,
			'compositeIndex' => static::$_compositeIndex,
			'customIndices'  => static::$_customIndices,
		];
		$sourceTable->copy($temporaryTable, $params);

		// Drop the original table.
		try {
			$destinationTable->delete();
		} catch (\Exception $e) {
			// Nevermind.
		}

		// Rename the temporary table.
		$temporaryTable->rename($destinationTable->name);

		return true;
	}

	static function cache() {
		try {

			$class = static::getClass();

			#return \Katu\Utils\Lock::run(['databases', static::getConnection()->config->database, 'views', 'cache', static::TABLE], 600, function($class) {

				$class::materializeSourceViews();

				$class = '\\' . ltrim($class, '\\');
				$class::copy($class::getView(), $class::getCachedTable());
				$class::updateLastCachedTime();

				return true;

			#}, $class);

		} catch (\Katu\Exceptions\LockException $e) {
			// Nevermind.
		}
	}

	static function cacheIfExpired() {
		if (static::isCacheExpiredAdvance()) {
			try {
				return static::cache();
			} catch (\Exception $e) {
				\App\Extensions\Errors\Handler::log($e);
			}
		}
	}

	static function materialize() {
		try {

			return \Katu\Utils\Lock::run(['databases', static::getConnection()->config->database, 'views', 'materialize', static::TABLE], 600, function($class) {

				$class::materializeSourceViews();

				$class = '\\' . ltrim($class, '\\');
				$class::copy($class::getView(), $class::getMaterializedTable());
				$class::updateLastMaterializedTime();

				return true;

			}, static::getClass());

		} catch (\Katu\Exceptions\LockException $e) {
			\App\Extensions\Errors\Handler::log($e);
		}
	}

	static function materializeIfExpired() {
		if (static::isMaterializeExpiredAdvance()) {
			try {
				return static::materialize();
			} catch (\Exception $e) {
				\App\Extensions\Errors\Handler::log($e);
			}
		}
	}

	static function materializeSourceViews() {
		foreach (static::getView()->getSourceViewsInMaterializedViews() as $view) {
			foreach ($view->getModelNames() as $class) {

				$class = '\\' . ltrim($class, '\\');
				$class::materializeIfExpired();

			}
		}

		return true;
	}

	static function getLastCachedTemporaryFile() {
		return new \Katu\Files\Temporary(['!databases', '!' . static::getConnection()->config->database, '!views', '!cached', '!' . static::TABLE]);
	}

	static function updateLastCachedTime() {
		return static::getLastCachedTemporaryFile()->set(microtime(true));
	}

	static function getLastCachedTime() {
		return (float)static::getLastCachedTemporaryFile()->get();
	}

	static function getLastMaterializedTemporaryFile() {
		return new \Katu\Files\Temporary(['!databases', '!' . static::getConnection()->config->database, '!views', '!materialized', '!' . static::TABLE]);
	}

	static function updateLastMaterializedTime() {
		return static::getLastMaterializedTemporaryFile()->set(microtime(true));
	}

	static function getLastMaterializedTime() {
		return (float)static::getLastMaterializedTemporaryFile()->get();
	}

	static function getAllViewClassNames() {
		$dir = (new \Katu\Files\File('app', 'Models'));
		if ($dir->exists()) {
			$dir->includeAllPhpFiles();
		}

		return array_values(array_filter(get_declared_classes(), function($class) {
			return is_subclass_of($class, '\\Katu\\Models\\View') && defined("$class::TABLE");
		}));
	}

	static function cacheAndMaterializeAll() {
		foreach (static::getAllViewClassNames() as $viewClass) {
			$viewClassName = '\\' . $viewClass;
			$viewClassName::cacheIfExpired();
			if ($viewClassName::isMaterializable()) {
				$viewClassName::materializeIfExpired();
			}
		}
	}

}
