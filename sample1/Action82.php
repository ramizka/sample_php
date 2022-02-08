<?php

namespace Maraquia\Partners;

use GeneralController;

class Action82 extends Action {

    public function check(?GeneralController $controller = null): bool {

        if ($controller){
            $controller->view->css_file = 'actions/82/lots.css';
        }

        return true;
    }
}
