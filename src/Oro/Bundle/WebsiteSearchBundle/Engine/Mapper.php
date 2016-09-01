<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\SearchBundle\Query\Query;

class Mapper
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
     * @param Query $query
     * @param array $item
     * @return array|null
     */
    public function mapSelectedData(Query $query, $item)
    {
        // TODO: This is a stub and it should be replaced by the real implementation of the mapper in BB-4076 or BB-4321
        return [
            'title' => 'Product title',
        ];
    }

    /**
     * @param string $entity
     * @return bool|array
     */
    public function getEntityConfig($entity)
    {
        // TODO: This is a stub and it should be replaced by the real implementation of the mapper in BB-4076 or BB-4321
        return $this->getMappingConfig()[$entity];
    }
}
