<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;

class Mapper
{
    /**
     * @param Query $query
     * @param array $item
     * @return array|null
     */
    public function mapSelectedData(Query $query, array $item)
    {
        $selects = $query->getSelect();

        if (empty($selects)) {
            return null;
        }

        $result = [];

        foreach ($selects as $select) {
            list ($type, $name) = Criteria::explodeFieldTypeName($select);

            $result[$name] = '';

            if (isset($item[$name])) {
                $value = $item[$name];
                if (is_array($value)) {
                    $value = array_shift($value);
                }

                $result[$name] = $value;
            }
        }

        return $result;
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
