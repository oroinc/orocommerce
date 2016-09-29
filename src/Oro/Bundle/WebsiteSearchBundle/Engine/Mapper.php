<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * It returns mappings for selected fields which
 * are used to create result item objects
 */
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
        $selectAliases = $query->getSelectAliases();

        if (empty($selects)) {
            return [];
        }

        $result = [];

        foreach ($selects as $select) {
            list ($type, $name) = Criteria::explodeFieldTypeName($select);

            if (isset($selectAliases[$name])) {
                $resultName = $selectAliases[$name];
            } elseif (isset($selectAliases[$select])) {
                $resultName = $selectAliases[$select];
            } else {
                $resultName = $name;
            }
            $result[$resultName] = '';

            if (isset($item[$resultName])) {
                $value = $item[$resultName];
                if (is_array($value)) {
                    $value = array_shift($value);
                }

                $result[$resultName] = $value;
            }
        }

        return $result;
    }
}
