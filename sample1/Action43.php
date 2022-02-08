<?php

namespace Maraquia\Partners;

use GeneralController;

class Action43 extends Action {
	
	public function check(?GeneralController $controller = null): bool {
		
		\Maraquia\Model\Payments::$_engines = array_replace_recursive(
			\Maraquia\Model\Payments::$_engines, [
				"paykeeper" =>  [
					"active" => true
				],
				"vtb_online" => [
					"active" => true
				],
				"cloudpayments" => [
					"active" => false
				],
				"sberbank" => [
					"active" => false
				],
			]
		);
	
		return true;
	}
}
