<?php

class Businessfactory_Roihuntereasy_Block_Adminhtml_System_Config_Cronbutton extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Set template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('businessfactory_roihuntereasy/system/config/cronbutton.phtml');
    }
    /**
     * Render button. Remove scope label.
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return button element html.
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    /**
     * Generate button html
     *
     * @return string
     */
    public function getCronButtonHtml()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'id'        => 'roihuntereasy_reset_button',
                'label'     => $this->helper('adminhtml')->__('Reset Cron'),
                'onclick'   => 'javascript:resetCron(); return false;'
            ));

        return $button->toHtml();
    }

    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getCronAjaxUrl()
    {
        return Mage::helper('adminhtml')->getUrl('roihuntereasy/reset/cron');
    }
}
