<?php

namespace Oro\Bundle\ProductBundle\Service;

use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\SegmentFiltersPurifier;

/**
 * Allow converting Segment definition into definition without excluded and included filters,
 * and incorporate excluded&included filters into definition.
 */
class ProductCollectionDefinitionConverter
{
    const INCLUDED_FILTER_KEY = 'included';
    const EXCLUDED_FILTER_KEY = 'excluded';
    const DEFINITION_KEY = 'definition';
    const INCLUDED_FILTER_ALIAS = 'incl';
    const EXCLUDED_FILTER_ALIAS = 'excl';

    /**
     * @var SegmentFiltersPurifier
     */
    private $filtersPurifier;

    public function __construct(SegmentFiltersPurifier $filterDefinitionPurifier)
    {
        $this->filtersPurifier = $filterDefinitionPurifier;
    }

    /**
     * Put excluded or\and included filters into definition.
     * Accept empty definition, in such case will create needed.
     *
     * @param string $rawDefinition
     * @param string|null $excluded
     * @param string|null $included
     *
     * @return string
     */
    public function putConditionsInDefinition($rawDefinition, $excluded = null, $included = null): string
    {
        $definition = $this->normalizeDefinition($rawDefinition);

        $definition['filters'] = $this->normalizeFilters($definition);

        if ($included) {
            if ($this->hasFilters($definition)) {
                $definition['filters'][] = 'OR';
            }
            $definition['filters'][] = $this->getFilter(
                NumberFilterType::TYPE_IN,
                $included,
                self::INCLUDED_FILTER_ALIAS
            );
        }

        if ($excluded) {
            if ($this->hasFilters($definition)) {
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
     * @param string $rawDefinition
     *
     * @return array
     */
    public function getDefinitionParts($rawDefinition): array
    {
        $definition = $this->normalizeDefinition($rawDefinition);
        $included = null;
        $excluded = null;
        if ($this->hasFilters($definition)) {
            $included = $this->getValueFromFilter(self::INCLUDED_FILTER_ALIAS, $definition['filters']);
            $excluded = $this->getValueFromFilter(self::EXCLUDED_FILTER_ALIAS, $definition['filters']);
            $definition['filters'] = $this->getUserDefinedFilter($definition);
        }

        return [
            self::DEFINITION_KEY => json_encode($definition),
            self::INCLUDED_FILTER_KEY => $included,
            self::EXCLUDED_FILTER_KEY => $excluded
        ];
    }

    /**
     * @param mixed $definition
     *
     * @return bool
     */
    public function hasFilters($definition)
    {
        if (!is_array($definition)) {
            $definition = $this->normalizeDefinition($definition);
        }

        return !empty($definition['filters']);
    }

    private function normalizeFilters(array $definition): array
    {
        $filters = [];
        if ($this->hasFilters($definition)) {
            $filters = $this->filtersPurifier->purifyFilters($definition['filters']);

            if (!empty($filters)) {
                $filters = [$filters];
            }
        }

        return $filters;
    }

    /**
     * @param string $rawDefinition
     * @return array
     */
    private function normalizeDefinition($rawDefinition): array
    {
        $definition = $rawDefinition ? json_decode($rawDefinition, true) : [];

        if (!is_array($definition)) {
            $definition = [];
        }

        return $definition;
    }

    /**
     * @param int $type
     * @param string $value
     * @param string $alias
     * @return array
     */
    private function getFilter($type, $value, $alias): array
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

    /**
     * @param string $alias
     * @param array $filters
     * @return null|string
     */
    private function getValueFromFilter($alias, array $filters)
    {
        foreach ($filters as $filter) {
            if (!empty($filter['alias']) && $filter['alias'] === $alias) {
                return $filter['criterion']['data']['value'];
            }
        }

        return null;
    }

    private function getUserDefinedFilter(array $definition): array
    {
        if (isset($definition['filters'][0])
            && is_array($definition['filters'][0])
            && array_key_exists(0, $definition['filters'][0])
        ) {
            return $definition['filters'][0];
        }

        return [];
    }
}
