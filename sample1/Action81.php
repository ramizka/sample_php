<?php

namespace Maraquia\Partners;

class Action81 extends Action {
	
	public function check(\GeneralController $controller = null): bool {

        $controller->view->totalBusyPeople = 4000;

		return true;
	}
}
