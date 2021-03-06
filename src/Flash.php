<?php

namespace Katu;

class Flash extends Session {

	const KEY = 'katu.flash';

	static function set() {
		static::init();

		if (count(func_get_args()) == 1) {
			$_SESSION[static::KEY][] = func_get_arg(0);
		} else {
			$_SESSION[static::KEY][func_get_arg(0)] = func_get_arg(1);
		}

		return true;
	}

}
