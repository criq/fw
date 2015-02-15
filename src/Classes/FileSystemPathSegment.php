<?php

namespace Katu\Classes;

class FileSystemPathSegment {

	public $name;
	public $prefixFolderDepth      = 1;
	public $prefixFolderLength     = 1;
	public $hashPrefixFolderDepth  = 3;
	public $hashPrefixFolderLength = 2;

	public function __construct($name = null) {
		$this->name = $name;

		if (is_string($this->name) && preg_match('#^\!(.+)$#', $this->name, $match)) {
			$this->name = $match[1];
			$this->disablePrefixFolder();
		}
	}

	public function getPathSegments() {
		$name = $this->name;

		// Trim slashes and explode into segments.
		if (is_string($name)) {
			$e = explode('/', trim($name, '/'));
			if (count($e) > 1) {
				$parent = $this;
				$name = new FileSystemPathSegments(array_map(function($i) use($parent) {
					return (new static($i))->setPrefixLenthsFromParent($parent);
				}, $e));
			} elseif (count($e) == 1) {
				$name = $e[0];
			}
		}

		// Serialize.
		if ($name instanceof FileSystemPathSegments) {
			$name = $name->getPathSegments();
		} elseif (!is_string($name)) {
			$name = sha1(serialize($name));
		}

		// Sanitize.
		if (is_string($name)) {
			$name = ltrim($name, '.');
			$name = preg_replace('#[^a-z0-9\.\-_]#i', '_', $name);
		}

		// Folder prefixes.
		if (is_string($name)) {

			// Hashes.
			if (preg_match('#^[0-9a-f]{8,}$#', $name)) {
				$prefixes = [];
				for ($i = 0; $i < $this->hashPrefixFolderDepth; $i++) {
					$prefixes[] = substr($name, $i * $this->hashPrefixFolderLength, $this->hashPrefixFolderLength);
				}
				$name = implode('/', array_merge($prefixes, [$name]));

			// Any other string.
			} else {
				$prefixes = [];
				for ($i = 0; $i < $this->prefixFolderDepth; $i++) {
					$prefixes[] = substr($name, $i * $this->prefixFolderLength, $this->prefixFolderLength);
				}
				$name = implode('/', array_merge($prefixes, [$name]));
			}

		}

		if (is_string($name)) {
			$name = trim($name, '/');
		}

		return $name;
	}

	public function disablePrefixFolder() {
		$this->setPrefixFolderDepth(0);
		$this->setPrefixFolderLength(0);

		return $this;
	}

	public function setPrefixFolderDepth($prefixFolderDepth) {
		$this->prefixFolderDepth = (int) $prefixFolderDepth;

		return $this;
	}

	public function setPrefixFolderLength($prefixFolderLength) {
		$this->prefixFolderLength = (int) $prefixFolderLength;

		return $this;
	}

	public function setPrefixLenthsFromParent($parent) {
		$this->prefixFolderDepth      = $parent->prefixFolderDepth;
		$this->prefixFolderLength     = $parent->prefixFolderLength;
		$this->hashPrefixFolderDepth  = $parent->hashPrefixFolderDepth;
		$this->hashPrefixFolderLength = $parent->hashPrefixFolderLength;

		return $this;
	}

}