<?php

namespace Oro\Bundle\ProductBundle\Service;

use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;

/**
 * Allow converting Segment definition into definition without excluded and included filters,
 * and incorporate excluded&included filters into definition.
 */
class ProductCollectionDefinitionConverter
{
    const EMPTY_SET = '0';
    const INCLUDED_FILTER_KEY = 'included';
    const EXCLUDED_FILTER_KEY = 'excluded';
    const DEFINITION_KEY = 'definition';
    const INCLUDED_FILTER_ALIAS = 'incl';
    const EXCLUDED_FILTER_ALIAS = 'excl';

    /**
     * Put excluded or\and included filters into definition.
     * Accept empty definition, in such case will create needed.
     *
     * @param string $definition
     * @param string|null $excluded
     * @param string|null $included
     *
     * @return string
     */
    public function putConditionsInDefinition($definition, $excluded = null, $included = null)
    {
        $definitionParts = $this->getDefinitionPartsArray($definition);
        $definition = $definitionParts[self::DEFINITION_KEY];

        if (!empty($definition['filters'])) {
            $definition['filters'] = [
                $definition['filters'],
                'OR'
            ];
        }

        // We always need to add this filter to understand in getDefinitionParts when we need to lower filters level
        $definition['filters'][] = $this->getFilter(
            NumberFilterType::TYPE_IN,
            $included ?? self::EMPTY_SET,
            self::INCLUDED_FILTER_ALIAS
        );

        if ($excluded) {
            if (!empty($definition['filters'])) {
                $definition['filters'][] = 'AND';
            }
            $definition['filters'][] = $this->getFilter(
                NumberFilterType::TYPE_NOT_IN,
                $excluded,
                self::EXCLUDED_FILTER_ALIAS
            );
        }

        return json_encode($definition);
    }

    /**
     * Receive definition and return definition without excluded&included,
     * the later will be returned as separate elements. Example or returned array:
     * [
     *      'definition' => definition string as json encoded array, without excluded&included
     *      'excluded'   => excluded filter value string, if were found in definition
     *      'included'   => included filter value string, if were found in definition
     * ]
     *
     * @param string $definition
     *
     * @return array
     */
    public function getDefinitionParts($definition)
    {
        $parts = $this->getDefinitionPartsArray($definition);

        $parts[self::DEFINITION_KEY] = json_encode($parts[self::DEFINITION_KEY]);

        return $parts;
    }

    /**
     * @param string $definition
     * @return array
     */
    private function getDefinitionPartsArray($definition)
    {
        $definition = $this->normalizeDefinition($definition);
        $excluded = $this->removeFilterFromDefinition($definition, self::EXCLUDED_FILTER_ALIAS, 'AND');
        $included = $this->removeFilterFromDefinition($definition, self::INCLUDED_FILTER_ALIAS, 'OR');

        // Included is always set if putDefinitionParts was called (if no included is given then EMPTY_SET is used)
        if (null !== $included && isset($definition['filters'][0])) {
            $definition['filters'] = $definition['filters'][0];
        }

        if ($included === self::EMPTY_SET) {
            $included = null;
        }

        return [
            self::DEFINITION_KEY => $definition,
            self::INCLUDED_FILTER_KEY => $included,
            self::EXCLUDED_FILTER_KEY => $excluded
        ];
    }

    /**
     * @param string $definition
     * @return array
     */
    private function normalizeDefinition($definition)
    {
        $definition = $definition ? json_decode($definition, true) : [];

        if (!is_array($definition)) {
            $definition = [];
        }

        return $definition;
    }

    /**
     * @param array $definition
     * @param string $filterAlias
     * @param string $filterOperator
     *
     * @return string|null
     */
    private function removeFilterFromDefinition(&$definition, $filterAlias, $filterOperator)
    {
        $filterValue = null;
        if (!isset($definition['filters']) || !is_array($definition['filters'])) {
            return $filterValue;
        }

        $filters = $definition['filters'];
        $filter = end($filters);

        if (isset($filter['alias']) && $filter['alias'] === $filterAlias) {
            $filterValue = $filter['criterion']['data']['value'];
            array_pop($filters);

            //try to remove operator
            $operatorOrFilter = end($filters);
            if ($operatorOrFilter === $filterOperator) {
                array_pop($filters);
            }
        }

        $definition['filters'] = $filters;

        return $filterValue;
    }

    /**
     * @param int $type
     * @param string $value
     * @param string $alias
     * @return array
     */
    private function getFilter($type, $value, $alias)
    {
        return [
            'alias' => $alias,
            'columnName' => 'id',
            'criterion' => [
                'filter' => 'number',
                'data' => [
                    'value' => $value,
                    'type' => $type
                ]
            ]
        ];
    }
}
