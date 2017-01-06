<?php
class Businessfactory_Roihuntereasy_Model_Options
{
    /**
     * Provide available options as a value/label array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value'=>1, 'label'=>'One'),
            array('value'=>2, 'label'=>'Two'),
            array('value'=>3, 'label'=>'Three'),
            array('value'=>4, 'label'=>'Four')
        );
    }
}