<?php
class Nls_Crowd_Model_Observer
{
    protected $request;
    const CONFIG_PATH_CROWD_ENABLED = 'nls_crowd/config/enabled';
    const CONFIG_PATH_CROWD_URL = 'nls_crowd/config/service_url';
    const CONFIG_PATH_CROWD_APP_NAME = 'nls_crowd/config/app_name';
    const CONFIG_PATH_CROWD_APP_PASSWORD = 'nls_crowd/config/app_credential';

    public function adminhtmlControllerActionPredispatchStart($event)
    {
        $this->request = Mage::app()->getRequest();
        $postLogin = $this->request->getPost('login');

        // The user has just completed the login form
        if ( false && !is_null($postLogin)) {
            $username = isset($postLogin['username']) ? $postLogin['username'] : '';
            $password = isset($postLogin['password']) ? $postLogin['password'] : '';

            // Is Crowd auth enabled and are Crowd credentials available ?
            if (true) {

            }


            //

            $providers = Mage::getStoreConfig(self::CONFIG_PATH_LOGIN_PROVIDERS);
            foreach ($providers as $provider) {
                /* @var $provider Hackathon_LoginProviderFramework_Interface_LoginProviderInterface */
                $provider = Mage::getModel($provider);
                if ($provider->authenticate($username, $password)) {
                    $this->userAuthenticated($username, $provider->getRoleForUser($username));
                }
            }
        }
        //die('test2');
    }
    protected function userAuthenticated($username, $role)
    {
        // TODO core/session is initialized in
        // app/code/core/Mage/Core/Controller/Varien/Action.php:495
        // this are 30 lines after the event we are observing (adminhtmlControllerActionPredispatchStart)
        Mage::getSingleton(
            'core/session',
            array('name' => Mage_Adminhtml_Controller_Action::SESSION_NAMESPACE)
        )->start();
        /* @var $session Mage_Admin_Model_Session */
        $session = Mage::getSingleton('admin/session');
        /* @var $user Mage_Admin_Model_User */
        $user = Mage::getModel('admin/user');
        $user->loadByUsername($username);
        if (!$user->getId()) {
            $name = uniqid();
            $user->setUsername($username);
            $user->setFirstname($name);
            $user->setLastname($name);
            $user->setPassword('test');
            $user->setEmail($name . '@domain.invalid');
            $user->setIsActive(true);
            $role = Mage::getModel('admin/role')->load($role, 'role_name');
            if (!$role->getId()) {
                Mage::throwException('Role not found.');
            }
            $user->save();
            $user->setRoleIds(array($role->getId()))
                ->setRoleUserId($user->getUserId())
                ->saveRelations();
        }
        // TODO copied from app/code/core/Mage/Admin/Model/User.php:339
        // any better idea?
        if ($user->getIsActive() != '1') {
            Mage::throwException(Mage::helper('adminhtml')->__('This account is inactive.'));
        }
        if (!$user->hasAssigned2Role($user->getId())) {
            Mage::throwException(Mage::helper('adminhtml')->__('Access denied.'));
        }
        $session->setIsFirstPageAfterLogin(true);
        $session->setUser($user);
        $session->setAcl(Mage::getResourceModel('admin/acl')->loadAcl());
    }
}