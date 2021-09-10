<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\SearchBundle\Engine\Orm\BaseDriver;
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
     * @return array
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
            list($type, $name) = Criteria::explodeFieldTypeName($select);

            if (isset($selectAliases[$name])) {
                $resultName = $selectAliases[$name];
            } elseif (isset($selectAliases[$select])) {
                $resultName = $selectAliases[$select];
            } else {
                $resultName = $name;
            }

            $value = null;

            // if flat object field
            if (str_contains($name, '.')) {
                $value = $this->parseFlatValue($item, $type, $name);
            }

            if (null === $value) {
                if (isset($item[$name])) {
                    $value = $this->parseValue($item[$name], $type);
                } else {
                    $name = str_replace('.', BaseDriver::SPECIAL_SEPARATOR, $name);
                    if (isset($item[$name])) {
                        $value = $this->parseValue($item[$name], $type);
                    }
                }
            }

            $result[$resultName] = $value !== null ? $value : '';
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    protected function parseValue($value, $type)
    {
        if (is_array($value)) {
            $value = array_shift($value);
        }

        if (is_numeric($value)) {
            if ($type === Query::TYPE_INTEGER) {
                $value = (int)$value;
            } elseif ($type === Query::TYPE_DECIMAL) {
                $value = (float)$value;
            }
        }

        return $value;
    }

    /**
     * @param array $item
     * @param $name
     * @return mixed
     */
    protected function parseFlatValue(array $item, $type, $name)
    {
        $nameParts = explode('.', $name);
        // convert string to array
        $dataArray = &$item;
        foreach ($nameParts as $part) {
            if (array_key_exists($part, $dataArray)) {
                $dataArray = &$dataArray[$part];
            } else {
                $dataArray = null;
                break;
            }
        }

        return $this->parseValue($dataArray, $type);
    }
}
