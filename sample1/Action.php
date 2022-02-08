<?php

namespace Maraquia\Partners;

abstract class Action implements ActionInterface {

    /**
     * Action Model
     */
    protected ?\Maraquia\Model\Actions $_action;

    /**
     * Action ID (model ID)
     */
    protected ?int $_action_id;


    /**
     * Language
     */
    protected string $lang;


    /**
     * Variables to use in templates
     */
    protected array $variables = [];

    /**
     * Construct specific action object
     *
     * @param \Maraquia\Model\Actions|null $action
     * @param array $params
     */
    public function __construct(?\Maraquia\Model\Actions $action = null, array $params = []){
		$this->_action = $action;
        if ($this->_action && $this->_action->getId()){
            $this->_action_id = $this->_action->getId();
        }

        $this->lang = $params['lang'] ?? \Maraquia\Lang::getCurrent();

		if (method_exists($this, "onConstruct")){
			$this->onConstruct($params);
		}
	}

    /**
     * Regenerate session for iframes: no cookies, add PHPSESSID to urls
     */
    protected function reloadSession(): void {

        $session_id = $_POST[session_name()] ?: $_GET[session_name()];

        if ($session_id){
            session_write_close();
            session_id($session_id);
        }

        \Session::getInstance()->restart(['use_trans_sid' => 1, 'use_cookies' => 0, 'use_only_cookies' => 0]);
    }

    /**
     * Get action ID
     *
     * @return int|null
     */
    public function getActionId(): ?int
    {
        return $this->_action_id;
    }

    /**
     * Get variable from array of variables or generate it using view
     *
     * @param string $var_name
     * @param string|null $lang
     * @return string|null
     */
    public function getVar(string $var_name, ?string $lang = null): ?string
    {

        $lang = $lang ?? $this->lang;

        if (isset($this->variables[$var_name])){
            $content = $this->variables[$var_name];
            if (is_array($content)){
                $content = $content[$lang] ?? reset($content);
            }
        } else {
            # Checking templates
            $content = $this->getVarFromTemplate($var_name, $lang);
        }

        return $content;
    }

    /**
     * Get variable from template
     *
     * @param string $var_name
     * @param string|null $lang
     * @return string|null
     */
    public function getVarFromTemplate(string $var_name, ?string $lang = null): ?string
    {
        $content = null;

        $view = new \View();
        $view->useTwig(true);

        $template_base_name = 'actions/'.$this->getActionId().'/'.$var_name;

        if ($view->templateExists($template_base_name.'_'.$lang)) {
            $content = $view->getTemplateContent($template_base_name . '_' . $lang);
        } elseif ($view->templateExists($template_base_name)){
            $content = $view->getTemplateContent($template_base_name);
        }

        return $content;
    }

}
