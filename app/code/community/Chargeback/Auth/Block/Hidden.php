<?php

class Chargeback_Auth_Block_Hidden extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{
  public function render(Varien_Data_Form_Element_Abstract $element)
  {
    return '';
  }
}