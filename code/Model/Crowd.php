<?php

class Nls_Crowd_Model_Crowd
{
    protected $serviceUrl;
    protected $appName;
    protected $appCredential;

    public function __construct($serviceUrl, $appName, $appCredential)
    {
        $this->serviceUrl = $serviceUrl;
        $this->appName = $appName;
        $this->appCredential = $appCredential;
    }

    /**
     * Try to authenticate user against the Crowd server
     *
     * @param $username
     * @param $password
     */
    public function authenticate($username, $password)
    {
        $request = $this->serviceUrl .
            '/crowd/rest/usermanagement/1/authentication?' .
            'username=' . htmlspecialchars($username);

        $data = json_encode(array('value' => $password));

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $request);
        curl_setopt($curl, CURLOPT_COOKIESESSION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($curl, CURLOPT_USERPWD, $this->appName.':'.$this->appCredential);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json'
        ));

        $return = curl_exec($curl);

        if (empty($return)) { // some kind of an error happened
            curl_close($curl);
            $this->redirectToLogin(Mage::helper('nls_crowd')->__("Can't contact Crowd Server"));
        } else {
            $info = curl_getinfo($curl);
            curl_close($curl); // close cURL handler

            if($info['http_code'] == 200) { // user is validated by the crowd server
                $user = json_decode($return);
                $this->setAuthenticatedUser($user);
            } else { // an error happened
                $error = null;
                if ($info['http_code'] == 401) { // wrong app credentials
                    $error = Mage::helper('nls_crowd')->__("Application failed to authenticate");
                } else {
                    $return = json_decode($return);
                    $error = $return->reason;
                }
                $this->redirectToLogin(Mage::helper('nls_crowd')->__($error));
            }
        }
    }

    /**
     * Redirect user to login form
     *
     * @param $error
     */
    public function redirectToLogin($error)
    {
        Mage::dispatchEvent('admin_session_user_login_failed',
            array('user_name' => '', 'exception' => ''));
        Mage::getSingleton('adminhtml/session')->addError($error);
        Mage::app()->getRequest()->setParam('messageSent', true);
    }

    /**
     * Create a Magento user and set the session with the valid user
     * Based on https://github.com/magento-hackathon/LoginProviderFramework/blob/master/app/code/community/Hackathon/LoginProviderFramework/Model/Observer.php
     *
     * @param $crowdUser
     */
    public function setAuthenticatedUser($crowdUser)
    {

        Mage::getSingleton(
            'core/session',
            array('name' => Mage_Adminhtml_Controller_Action::SESSION_NAMESPACE)
        )->start();

        /* @var $session Mage_Admin_Model_Session */
        $session = Mage::getSingleton('admin/session');
        /* @var $user Mage_Admin_Model_User */
        $user = Mage::getModel('admin/user');

        $user->loadByUsername($crowdUser->name);
        if (!$user->getId()) {
            $user->setUsername($crowdUser->name);
            $user->setFirstname($crowdUser->{'first-name'});
            $user->setLastname($crowdUser->{'last-name'});
            $user->setPassword('@@@fake@@@');
            $user->setEmail($crowdUser->email);
            $user->setIsActive($crowdUser->active);
            $role = Mage::getModel('admin/role')->load('Administrators', 'role_name');
            if (!$role->getId()) {
                Mage::throwException('Role not found.');
            }
            $user->save();
            $user->setRoleIds(array($role->getId()))
                ->setRoleUserId($user->getUserId())
                ->saveRelations();
        } else {
            // Update details
            $user->setFirstname($crowdUser->{'first-name'});
            $user->setLastname($crowdUser->{'last-name'});
            $user->setEmail($crowdUser->email);
            $user->setIsActive($crowdUser->active);
            $user->save();
        }

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