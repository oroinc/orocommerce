<?php

namespace OroB2B\Bundle\AttributeBundle\Migrations\Data\Demo\ORM;

use OroB2B\Bundle\AttributeBundle\Migrations\Data\ORM\AbstractLoadAttributeData;

class LoadAttributeDemoData extends AbstractLoadAttributeData
{

    public function __construct()
    {
        // TODO: Why don't we have an attribute factory that could be used?
        $this->attributes[] = $this->describeAttribute('OroB2B\Bundle\AttributeBundle\AttributeType\Boolean');
        $this->attributes[] = $this->describeAttribute('OroB2B\Bundle\AttributeBundle\AttributeType\Date');
        $this->attributes[] = $this->describeAttribute('OroB2B\Bundle\AttributeBundle\AttributeType\DateTime');
        $this->attributes[] = $this->describeAttribute('OroB2B\Bundle\AttributeBundle\AttributeType\Float');
        $this->attributes[] = $this->describeAttribute('OroB2B\Bundle\AttributeBundle\AttributeType\Integer');
        $this->attributes[] = $this->describeAttribute('OroB2B\Bundle\AttributeBundle\AttributeType\String');
        $this->attributes[] = $this->describeAttribute('OroB2B\Bundle\AttributeBundle\AttributeType\Text');
        $this->attributes[] = $this->describeAttribute(
            'OroB2B\Bundle\AttributeBundle\AttributeType\Select',
            [
                ['value' => 'Select option 01', 'order' => 5],
                ['value' => 'Select option 02', 'order' => 10]
            ]
        );
        $this->attributes[] = $this->describeAttribute(
            'OroB2B\Bundle\AttributeBundle\AttributeType\MultiSelect',
            [
                ['value' => 'Multiselect option 01', 'order' => 10],
                ['value' => 'Multiselect option 02', 'order' => 20]
            ]
        );
    }

    /**
     * @param string|object $attributeType
     * @param array $options
     * @return array
     */
    private function describeAttribute($attributeType, array $options = [])
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

        if ($options) {
            $attribute['options'] = $options;
        }

        return $attribute;
    }
}
