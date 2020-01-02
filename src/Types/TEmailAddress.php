<?php

namespace Katu\Types;

class TEmailAddress {

	public $value;

	public function __construct($value) {
		if (!static::isValid($value)) {
			throw new \Katu\Exceptions\InputErrorException("Invalid e-mail address.");
		}

		$this->value = (string) (trim($value));
	}

	public function __toString() {
		return (string)$this->value;
	}

	static function isValid($value) {
		if (preg_match('/^2/', \Katu\Utils\Composer::getVersion('egulias/email-validator'))) {

			$validator = new \Egulias\EmailValidator\EmailValidator;
			$multipleValidations = new \Egulias\EmailValidator\Validation\MultipleValidationWithAnd([
					new \Egulias\EmailValidator\Validation\RFCValidation,
			]);

			return $validator->isValid($value, $multipleValidations);

		} else {

			$validator = new \Egulias\EmailValidator\EmailValidator;

			return $validator->isValid($value);

		}
	}

}
