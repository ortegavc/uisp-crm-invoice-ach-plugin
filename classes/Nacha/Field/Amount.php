<?php

namespace App\Nacha\Field;

class Amount extends Number {

	public function __construct($value) {
		// float value, preserve decimal places
		$value = number_format((float)$value, 2, '.', '');

		// TODO: maybe its error
//		$exploded = explode('.', $value);
//
//		if (count($exploded) == 2 && $exploded[1] == '00') {
//		    $value = $exploded[0];
//        }

		// remove dots
		$value = str_replace('.', '', $value);

		if (strlen($value) > 10) {
			throw new InvalidFieldException('Amount "' . $value . '" is too large.');
		}

		parent::__construct($value, 10);
	}

}