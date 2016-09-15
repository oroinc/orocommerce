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
        $aliases = $query->getSelectAliases();

        if (empty($selects)) {
            return null;
        }

        $result = [];

        foreach ($selects as $select) {
            list ($type, $name) = Criteria::explodeFieldTypeName($select);

            $alias = isset($aliases[$select]) ? $aliases[$select] : $name;

            $result[$alias] = '';

            if (isset($item[$alias])) {
                $value = $item[$alias];
                if (is_array($value)) {
                    $value = array_shift($value);
                }

                $result[$alias] = $value;
            }
        }

        return $result;
    }
}
