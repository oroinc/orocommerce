<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;

/**
 * This is a stub implementation and will be replaced with real implementation in BB-4076.
 */
class Mapper extends AbstractSearchMappingProvider
{
    /**
     * @return array
     */
    public function getMappingConfig()
    {
        return [
            'Oro\Bundle\ProductBundle\Entity\Product' => [
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

    /**
     * @param string $entityClass
     * @return string
     */
    public function getEntityAlias($entityClass)
    {
        $mappingConfig = $this->getMappingConfig();

        return $mappingConfig[$entityClass]['alias'];
    }
}
