<?php

namespace Maraquia\Partners;

class Action71 extends Action {
	
	public function check(\GeneralController $controller = null): bool {
		
		\Maraquia\Model\Payments::$_engines = array_replace_recursive(
			\Maraquia\Model\Payments::$_engines, [
				"company" =>  [
					"active" => false
				],
			]
		);

		\Maraquia\Model\OrdersPrivate::$_fields['title']['show'] = false;
		\Maraquia\Model\OrdersPrivate::$_fields['title']['default_value'] = 'Лес Счетной палаты';

		\Maraquia\Model\OrdersPrivate::$_fields['giftToFriend']['show'] = false;
		\Maraquia\Model\OrdersPrivate::$_fields['giftToFriend']['default_value'] = 0;

		\Maraquia\Model\OrdersPrivate::$_fields['friend_name']['show'] = false;
		
		return true;
	}
}
