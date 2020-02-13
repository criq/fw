<?php

namespace Katu\Image\Filters;

class Resize extends \Katu\Image\Filter {

	public function apply($image) {
		$image->resize($this->params['width'], $this->params['height'], function($constraint) {
			$constraint->aspectRatio();
			if ($this->params['noUpsize'] ?? null) {
				$constraint->upsize();
			}
		});

		return true;
	}

}
