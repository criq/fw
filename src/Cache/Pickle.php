<?php

namespace Katu\Cache;

use Katu\Tools\DateTime\Timeout;
use Katu\Types\TSeconds;

class Pickle
{
	protected $name;

	public function __construct($name, $value = null)
	{
		$this->name = $name;

		if (!$this->getFile()->exists()) {
			$this->set(null);
		}

		if ($value) {
			$this->set($value);
		}
	}

	public function getName()
	{
		return $this->name;
	}

	public function getFile() : \Katu\Files\File
	{
		return new \Katu\Files\File(\Katu\App::getTemporaryDir(), 'pickles', \Katu\Files\File::generatePath($this->getName(), 'txt'));
	}

	public function get()
	{
		return unserialize($this->getFile()->get());
	}

	public function set($value)
	{
		return $this->getFile()->set(serialize($value));
	}

	public function delete()
	{
		return $this->getFile()->delete();
	}

	public function getDateTimeModified()
	{
		return $this->getFile()->getDateTimeModified();
	}

	public function hasContents() : bool
	{
		return !is_null($this->get());
	}

	public function getAge() : ?TSeconds
	{
		try {
			return $this->getFile()->getDateTimeModified()->getAge();
		} catch (\Throwable $e) {
			return null;
		}
	}

	public function isExpired(Timeout $timeout) : bool
	{
		return (bool)(is_null($this->get()) || !$timeout->fits($this->getDateTimeModified()));
	}

	public function isValid(?Timeout $timeout = null) : bool
	{
		if ($timeout && $this->isExpired($timeout)) {
			return false;
		}

		return $this->hasContents();
	}

	public function getOrCreate(Timeout $timeout, callable $callback)
	{
		if ($this->isExpired($timeout)) {
			$this->set(call_user_func_array($callback, array_slice(func_get_args(), 2)));
		}

		return $this->get();
	}
}
