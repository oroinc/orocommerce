<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

class Mapper
{
    /**
     * @return array
     */
    public function getMappingConfig()
    {
        return [
            'OroB2B\Bundle\ProductBundle\Entity\Product' => [
                'alias' => 'orob2b_product_WEBSITE_ID',
                'fields' => [
                    [
                        'name' => 'title_LOCALIZATION_ID',
                        'type' => 'text'
                    ]
                ]
            ]
        ];
    }
}
