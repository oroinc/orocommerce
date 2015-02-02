<?php

namespace OroB2B\Bundle\WebsiteBundle\Migrations\Data\Demo\ORM;

use OroB2B\Bundle\AttributeBundle\AttributeType\String;
use OroB2B\Bundle\AttributeBundle\Migrations\Data\ORM\AbstractLoadAttributeData;

class LoadAttributeDemoData extends AbstractLoadAttributeData
{
    /**
     * @var array
     */
    protected $attributes = [
        [
            'code' => 'productLine',
            'type' => String::NAME,
            'localized' => true,
            'system' => false,
            'required' => false,
            'unique' => false,
            'label' => 'productLine'
        ],
        [
            'code' => 'productScale',
            'type' => String::NAME,
            'localized' => true,
            'system' => false,
            'required' => false,
            'unique' => false,
            'label' => 'productScale'
        ],
        [
            'code' => 'productVendor',
            'type' => String::NAME,
            'localized' => true,
            'system' => false,
            'required' => false,
            'unique' => false,
            'label' => 'productVendor'
        ],
        [
            'code' => 'productDescription',
            'type' => String::NAME,
            'localized' => true,
            'system' => false,
            'required' => false,
            'unique' => false,
            'label' => 'productDescription'
        ]
    ];
}
