<?php

namespace OroB2B\Bundle\AttributeBundle\Migrations\Data\Demo\ORM;

use OroB2B\Bundle\AttributeBundle\Migrations\Data\ORM\AbstractLoadAttributeData;

class LoadAttributeDemoData extends AbstractLoadAttributeData
{

    public function __construct()
    {
        // TODO: Why don't we have an attribute factory that could be used?
        $this->arrtibutes[] = $this->describeAttribute('OroB2B\Bundle\AttributeBundle\AttributeType\Boolean');
        $this->arrtibutes[] = $this->describeAttribute('OroB2B\Bundle\AttributeBundle\AttributeType\Date');
        $this->arrtibutes[] = $this->describeAttribute('OroB2B\Bundle\AttributeBundle\AttributeType\DateTime');
        $this->arrtibutes[] = $this->describeAttribute('OroB2B\Bundle\AttributeBundle\AttributeType\Float');
        $this->arrtibutes[] = $this->describeAttribute('OroB2B\Bundle\AttributeBundle\AttributeType\Integer');
        $this->arrtibutes[] = $this->describeAttribute('OroB2B\Bundle\AttributeBundle\AttributeType\String');
        $this->arrtibutes[] = $this->describeAttribute('OroB2B\Bundle\AttributeBundle\AttributeType\Text');
        $this->arrtibutes[] = $this->describeAttribute('OroB2B\Bundle\AttributeBundle\AttributeType\Boolean');
        $this->arrtibutes[] = $this->describeAttribute('OroB2B\Bundle\AttributeBundle\AttributeType\Boolean');
    }

    /**
     * @param string|object $attributeType
     * @return array
     */
    private function describeAttribute($attributeType)
    {
        $class = new \ReflectionClass($attributeType);
        $name = $class->getConstant('NAME');
        $attribute = [
            'code'      => $name,
            'type'      => $name,
            'localized' => false,
            'system'    => false,
            'required'  => false,
            'unique'    => false,
            'label'     => $name,
        ];
        return $attribute;
    }
}
