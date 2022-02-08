<?php

namespace Maraquia\Partners;

use GeneralController;

interface ActionInterface {

    /**
     * Check if action is valid
     *
     * @param GeneralController|null $controller
     * @return bool
     */
    public function check(?GeneralController $controller = null): bool;


    /**
     * Return action ID
     *
     * @return int|null
     */
    public function getActionId(): ?int;


    /**
     * Get variable for using in templates
     *
     * @param string $var_name
     * @param string|null $lang
     * @return string
     */
    public function getVar(string $var_name, ?string $lang = null): ?string;
}