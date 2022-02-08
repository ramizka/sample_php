<?php

namespace Maraquia\Partners;

use GeneralController;

class Action84 extends Action {
	
	public function check(?GeneralController $controller = null): bool {

        if ($controller) {
            $controller->view->totalBusyPeople = 50;
        }

		return true;
	}
}
