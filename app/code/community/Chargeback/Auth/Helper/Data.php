<?php

class Chargeback_Auth_Helper_Data extends Mage_Core_Helper_Abstract {
  public $name = 'chargeback';

  // Get user role by name 'chargeback'
  public function role()
  {
    return Mage::getModel('admin/role')->getCollection()->addFieldToFilter('role_name',array('eq'=>$this->name))->load()->getFirstItem();
  }

  // Get OAuth role by name 'chargeback'
  public function oauthRole()
  {
    return Mage::getModel('api2/acl_global_role')->getCollection()->addFieldToFilter('role_name',array('eq'=>$this->name))->load()->getFirstItem();
  }

  // Get user by name 'chargeback'
  public function consumer()
  {
    return Mage::getModel('admin/user')->getCollection()->addFieldToFilter('username',array('eq'=>$this->name))->load()->getFirstItem();
  }

  // Generate URL for disputer to accept
  public function connect()
  {
    $pass = $this->createAPIAccount();
    $token = Mage::getSingleton('admin/session')->getCbAuthToken();
    return Mage::getStoreConfig('chargeback/general/url')."auth_tokens/connect?_cb_auth_token=$token&username=$this->name&password=$pass&name=Magento&url=".Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL).'api/rest&security_questions[][question]=admin_path&security_questions[][answer]='.Mage::getConfig()->getNode('admin/routers/adminhtml/args/frontName');
  }

  public function resetPluginSettingsRemoveUser()
  {
    // Delete OAuth role
    if($this->oauthRole()->getRoleName() == $this->name){
      $this->oauthRole()->delete()->save();
    }
    // Delete user role
    if($this->role()->getRoleName() == $this->name){
      $this->role()->delete()->save();
    }
    // Delete user role
    if($this->consumer()->getUsername() == $this->name){
      $this->consumer()->delete()->save();
    }
  }

  public function getComplete()
  {
    return ($this->oauthRole()->getRoleName() == $this->name) && ($this->role()->getRoleName() == $this->name) && ($this->consumer()->getUsername() == $this->name);
  }

  public function createAPIAccount()
  {
    $apiPassword = md5(time());
    $this->resetPluginSettingsRemoveUser();

    // Creates the OAuth role
    $oauth_role = Mage::getModel('api2/acl_global_role')
      ->setRoleName($this->name)
      ->setCurrentPassword($apiPassword)
      ->save();

    // Creates the permisisons for the OAuth role above
    $oauth_rule = Mage::getModel('api2/acl_global_rule')
      ->setRoleId($oauth_role->getId())
      ->setResourceId("all")
      ->setPrivilege(null)
      ->save();

    // Creates admin user named chargeback
    $user = Mage::getModel('admin/user')
      ->setData(array(
        'username'  => $this->name,
        'firstname' => $this->name,
        'lastname'  => $this->name,
        'email'     => 'apiuser@chargeback.com',
        'password'  => $apiPassword,
        'api2_roles'=> array($oauth_role->getId()),
        'is_active' => 1
      ))->save();

    // Assigns the user to the main admin role
    Mage::getModel("admin/role")
      ->setParentId(1)
      ->setTreeLevel(2)
      ->setRoleType('U')
      ->setRoleName($this->name)
      ->setUserId($user->getId())
      ->save();

    $oauth = Mage::helper('oauth');

    // Creates the OAuth key and secret that must be used after successful login
    $key = $oauth->generateConsumerKey();
    $secret = $oauth->generateConsumerSecret();
    Mage::getModel('oauth/consumer')
      ->setData(array(
       'key'    => $key,
       'secret' => $secret,
       'name'   => $this->name,
       'current_password' => $apiPassword
    ))->save();

    $existing = Mage::getModel('api2/acl_filter_attribute')->getCollection()->addFieldToFilter('user_type',array('eq'=>'admin'))->load()->getFirstItem();
    if($existing){
      $existing
        ->setUserType('admin')
        ->setResourceId('all')
        ->setOperation('')
        ->setAllowedAttributes(null)
        ->save();
    } else {
      Mage::getModel('api2/acl_filter_attribute')
        ->setData(array(
          'user_type'          => 'admin',
          'resource_id'        => "all",
          'operation'          => '',
          'allowed_attributes' => null
      ))->save();
    }
    return $apiPassword.':'.$key.':'.$secret;

  }

}