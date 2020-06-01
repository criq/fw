<?php

namespace Katu\Utils;

class Pickle
{
	public $path;
	public $name;

	public function __construct($name)
	{
		$this->path = new \Katu\Utils\File(debug_backtrace()[0]['file']);
		$this->name = $name;

		if (!$this->getFile()->exists()) {
			$this->set(null);
		}
	}

	public function getFile()
	{
		return new \Katu\Utils\File(TMP_PATH, 'pickles', ltrim(strtr($this->path, [
			'\\' => '/',
			'.' => '_',
		]), '/'), $this->name . '.txt');
	}

	public function get()
	{
		return unserialize($this->getFile()->get());
	}

	public function set($value)
	{
		return $this->getFile()->set(serialize($value));
	}
}
