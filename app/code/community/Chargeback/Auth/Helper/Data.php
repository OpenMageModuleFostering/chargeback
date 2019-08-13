<?php

class Chargeback_Auth_Helper_Data extends Mage_Core_Helper_Abstract {
  public function checkAPIAccount()
  {
    if (is_object($this->role()) && $this->role()->getId() > 0) {
      if (is_object($this->consumer()) && $this->consumer()->getId() > 0) {
        return $this->consumer();
      }
    }
    return false;
  }

  public function role()
  {
    return Mage::getModel('api/roles')->getCollection()->addFieldToFilter('role_name',array('eq'=>"Chargeback Admin"))->load()->getFirstItem();
  }

  public function consumer()
  {
    return Mage::getModel('api/user')->getCollection()->addFieldToFilter('username',array('eq'=>'chargeback'))->load()->getFirstItem();
  }

  public function buildApiUrl(/*.string.*/ $token, /*.string.*/ $key, /*.string.*/ $pass)
  {
    return Mage::getStoreConfig('chargeback/general/url')."auth_tokens/connect?_cb_auth_token=$token&username=$key&password=$pass&name=Magento&url=".Mage::getBaseUrl().'api/xmlrpc';
  }

  public function connect()
  {
    $this->createAPIAccount();
    return $this->buildApiUrl(Mage::getSingleton('admin/session')->getCbAuthToken(),'chargeback',Mage::getSingleton('admin/session')->getData('chargeback_password'));
  }

  public function resetPluginSettingsRemoveUser()
  {
    $this->role()->delete()->save();
    $this->consumer()->delete()->save();
    $this->setComplete(false);
  }

  public function setComplete($complete = true)
  {
    Mage::getConfig()->saveConfig('chargeback/general/completed', $complete ? true : false, 'default', 0);
  }

  public function getComplete()
  {
    Mage::app()->getStore()->resetConfig();
    return Mage::getStoreConfig('chargeback/general/completed');
  }

  public function createAPIAccount()
  {
    $account = $this->checkAPIAccount();

    if (!is_object($account)) {

      $apiPassword = md5(time());

      $api_role = Mage::getModel('api/roles')
        ->setName('Chargeback Admin')
        ->setRoleType('G')
        ->save();

      Mage::getModel('api/rules')
        ->setRoleId($api_role->getId())
        ->setResources(array('all'))
        ->saveRel();

      $userapi = Mage::getModel('api/user')
        ->setData(array(
          'username' => 'chargeback',
          'firstname' => 'Chargeback',
          'lastname' => 'User',
          'email' => 'apiuser@chargeback.com',
          'is_active' => 1,
          'api_key' => $apiPassword,
          'api_key_confirmation' => $apiPassword,
          'roles' => array($api_role->getId()) // your created custom role
        ));


      $userapi->save();

      $userapi->setRoleIds(array($api_role->getId()))->setRoleUserId($userapi->getUserId())
        ->saveRelations();

      Mage::getSingleton('admin/session')->setData('chargeback_password',$apiPassword);

    }

  }

}