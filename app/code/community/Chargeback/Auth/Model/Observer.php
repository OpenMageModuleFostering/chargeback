<?php
/**
 * Class Chargeback_Auth_Model_Observer
 *
 *
 * @author  Matt Kammersell <matt.kammersell@chargeback.com>
 */
class Chargeback_Auth_Model_Observer
{

	public function system_configuration_view(Varien_Event_Observer $observer)
	{
		$helper = Mage::helper('chargeback_auth');
		$admin 	= 	Mage::getSingleton('admin/session');
		$action = $observer->getEvent()->getControllerAction();
		$r 			= $action->getRequest();

		if ($r->getControllerName()."::".$r->getActionName()!=="system_config::edit") return;

		if ($r->getParam('remove') == "1" && $r->getParam('_cb_auth_token') == null) {
			$helper->resetPluginSettingsRemoveUser();
			return;
		}

    Mage::app()->getCacheInstance()->cleanType('config');
    Mage::dispatchEvent('adminhtml_cache_refresh_type', array('type' => 'config'));

		/* @var $action Mage_Adminhtml_Controller_Action */
		if ($r->getParam("_cb_auth_token",false) && !$r->getParam("cb_return_status",false)) {
       $admin->setCbAuthToken($r->getParam('_cb_auth_token'));
			return;
		}

		if ($r->getParam("cb_return_status") == "success") {
			$admin->unsetData('chargeback_password');
		}

	}
}