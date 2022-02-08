<?php

namespace Maraquia\Partners;

class Action80 extends Action {
	
	public function check(\GeneralController $controller = null): bool {
		
		\Maraquia\Model\Payments::$_engines = array_replace_recursive(
			\Maraquia\Model\Payments::$_engines, [
				"company" =>  [
					"active" => false
				],
			]
		);
		
		\Maraquia\Model\OrdersPrivate::$_fields['title']['label']['ru'] = 'Пожелание';
		\Maraquia\Model\OrdersPrivate::$_fields['title']['label']['en'] = 'Wish';
		\Maraquia\Model\OrdersPrivate::$_fields['title']['placeholder']['ru'] = 'Пожелание';
		\Maraquia\Model\OrdersPrivate::$_fields['title']['placeholder']['en'] = 'Wish';
		\Maraquia\Model\OrdersPrivate::$_fields['title']['default_value'] = 'Поздравляю с Днем Рождения!';
		
		\Maraquia\Model\OrdersPrivate::$_fields['giftToFriend']['show'] = false;
		\Maraquia\Model\OrdersPrivate::$_fields['giftToFriend']['default_value'] = 0;

		\Maraquia\Model\OrdersPrivate::$_fields['friend_name']['show'] = false;

		return true;
	}
}
