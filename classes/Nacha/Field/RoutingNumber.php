<?php

namespace App\Nacha\Field;

class RoutingNumber extends Number {
	public function __construct($value) {
		parent::__construct($value, 10);

//		if (!preg_match('/^[0-9]{10}$/', (string)$value)) {
//			throw new InvalidFieldException('Routing "' . $value . '" must be a 10 digit numbe1r.');
//		}
	}
}
