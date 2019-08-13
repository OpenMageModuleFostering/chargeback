<?php

class Chargeback_Auth_Block_Completed extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{
	public function render(Varien_Data_Form_Element_Abstract $element)
	{
		Mage::app()->getCacheInstance()->cleanType('config');
		Mage::dispatchEvent('adminhtml_cache_refresh_type', array('type' => 'config'));

		return $this->getBlockHtml('connect');
	}
}