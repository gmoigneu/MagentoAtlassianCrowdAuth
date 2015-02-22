<?php

class Nls_Crowd_Model_Observer
{
    protected $request;
    const CONFIG_PATH_CROWD_ENABLED = 'nls_crowd/config/enabled';
    const CONFIG_PATH_CROWD_URL = 'nls_crowd/config/service_url';
    const CONFIG_PATH_CROWD_APP_NAME = 'nls_crowd/config/app_name';
    const CONFIG_PATH_CROWD_APP_PASSWORD = 'nls_crowd/config/app_credential';

    /**
     * Catch admin controller predispatch & check for login data
     * 
     * @param $event
     */
    public function adminhtmlControllerActionPredispatchStart($event)
    {
        $this->request = Mage::app()->getRequest();
        $postLogin = $this->request->getPost('login');

        // The user has just completed the login form
        if (!is_null($postLogin)) {
            $username = isset($postLogin['username']) ? $postLogin['username'] : '';
            $password = isset($postLogin['password']) ? $postLogin['password'] : '';
            // Is Crowd auth enabled and are Crowd credentials available ?
            if (Mage::getConfig(self::CONFIG_PATH_CROWD_ENABLED)) {
                $crowd = new Nls_Crowd_Model_Crowd(
                    Mage::getStoreConfig(self::CONFIG_PATH_CROWD_URL),
                    Mage::getStoreConfig(self::CONFIG_PATH_CROWD_APP_NAME),
                    Mage::getStoreConfig(self::CONFIG_PATH_CROWD_APP_PASSWORD)
                );
                $crowd->authenticate($username, $password);
            }
        }
    }
}