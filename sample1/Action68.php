<?php

namespace Maraquia\Partners;

use GeneralController;
use Maraquia\Model\Payments;

class Action68 extends Action {

    /**
     * User ticket from multibonus API
     *
     * @var string|null
     */
    private $user_ticket;

    /**
     * People subclass
     *
     * @var Action68People|null
     */
    private $_people;

	protected function onConstruct(array $params = []){
        $this->_action_id = 68;
        $this->_people = new Action68People();

		if (!isset($params["session_handling"]) || $params["session_handling"] === true){
            $this->reloadSession();
		}
	}

    /**
     * Get UserTicket for API
     *
     * @return string|null
     */
    public function getUserTicket(): ?string {
        return $this->user_ticket;
    }

    public function check(?GeneralController $controller = null): bool {

		 $auth = \Auth::getInstance('People');
		 if ($auth->get('action_id') != $this->_action_id){
		 	return false;
		 }

		$temp_user = $auth->get('multibonus_temp_user');
		if (!$temp_user){
			return false;
		}
        $this->user_ticket = $temp_user;
        
        if ($controller){			
			$controller->view->disable_logout = true;
			$controller->view->passLoadUlogin = true;
			if ($auth->get('multibonus_source') == 'mp'){
				$controller->view->body_class = 'remove-share';
			}
			$controller->view->addJs(['https://multibonus.ru/scripts/api/online-category_v0.1.js', '/js/actions/68.js']);
			$controller->view->css_file = 'actions/68/lots.css';
		}

		foreach (Payments::$_engines as &$v){
			$v["active"] = false;
		}
		unset($v);
		
		
		Payments::$_engines = array_replace_recursive(
			Payments::$_engines, [
				"multibonus" =>  [
					"active" => true
				]
			]
		);

		return true;
	}



    /**
     * Init and check whitelabel action
     *
     * @param GeneralController $controller
     * @return bool
     * @throws \Exception
     */
    public function whitelabelInit(GeneralController $controller): bool {
        $UserTicket = (string) $controller->http_vars["UserTicket"];
		if (empty($UserTicket)){
			$controller->error404();
			return false;
		}

		if (!$this->_people->check($UserTicket)){
			$controller->error404();
			return false;
		}
		
		$controller->people = $this->_people->getPeople();
		$controller->people_valid = true;
        $controller->view->people_valid = $controller->people_valid;
        $controller->view->people = $controller->people;
		$controller->filters['query']['UserTicket'] = $UserTicket;
        $controller->view->addJs(['https://multibonus.ru/scripts/api/online-category_v0.1.js', '/js/actions/68.js']);
        $controller->view->passLoadUlogin = true;
        $controller->view->setName('main/plantations/actionsWhitelabel/'.$this->_action_id);

        $this->_people->login([
            'action_id' => $this->_action_id,
            'multibonus_temp_user' => $UserTicket,
            'multibonus_source' => (!empty($controller->http_vars["referrer"]) && $controller->http_vars["referrer"] == 'multibonusmp') ? 'mp' : 'site'
        ]);

		return true;
	}




}
