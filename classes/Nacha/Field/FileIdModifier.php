<?php

namespace App\Nacha\Field;

class FileIdModifier extends StringHelper {
	public function __construct($value) {
		parent::__construct(strtoupper($value), 1);

		if (!preg_match('/^[A-Z0-9]$/', (string)$value)) {
			throw new InvalidFieldException('File Id Modifier "' . $value . '" must be A-Z 0-9.');
		}
	}
}
