<?php

namespace Oro\Bundle\ProductBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\StringFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * The filter by a composed sku string value.
 *
 * Notes: Composed SKU - a string that include substrings separated by {@see self::$separator} symbol.
 * Each substring here is a SKU of the Product itself or SKU of related Product.
 *
 * Examples of Composed SKU strings (assumed that "|" is a custom separator):
 *  1) |2CF67| - Composed SKU for simple product ("2CF67" - simple product SKU );
 *  2) |6BC45|6BC46| - Composed SKU for Configurable Product.
 *      ("6BC45" and "6BC46" - SKUs of the product variants of Configurable Product "2TK59");
 *      SKU of the Configurable Product "2TK59" itself doesn't include in the Composed SKU
 *      because we didn't want to filter Products by Configurable Product SKU.
 *  3) |3UK92|2JD90|2CF67| - Composed SKU for Product Kit.
 *      ("3UK92" - Product Kit SKU, "2JD90" and "2CF67" - Kit Item Product SKUs).
 */
class ComposedSkuStringFilter extends StringFilter
{
    private string $separator = '';

    public function setSeparator(string $separator): self
    {
        $this->separator = $separator;

        return $this;
    }

    #[\Override]
    protected function parseValue(array $data)
    {
        switch ($data['type']) {
            case TextFilterType::TYPE_STARTS_WITH:
                $data['value'] = sprintf('%%%s%s%%', $this->separator, $data['value']);
                $data['type'] = TextFilterType::TYPE_CONTAINS;
                break;
            case TextFilterType::TYPE_ENDS_WITH:
                $data['value'] = sprintf('%%%s%s%%', $data['value'], $this->separator);
                $data['type'] = TextFilterType::TYPE_CONTAINS;
                break;
            case TextFilterType::TYPE_IN:
            case TextFilterType::TYPE_NOT_IN:
                $data['value'] = array_map(
                    fn ($value) => sprintf('%%%s%s%s%%', $this->separator, $value, $this->separator),
                    array_map('trim', explode(',', $data['value']))
                );
                break;
            case TextFilterType::TYPE_EQUAL:
                $data['value'] = sprintf('%%%s%s%s%%', $this->separator, $data['value'], $this->separator);
                $data['type'] = TextFilterType::TYPE_CONTAINS;
                break;
            default:
                return parent::parseValue($data);
        }

        return $data;
    }

    #[\Override]
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        $parameterNames = [];
        switch ($data['type']) {
            case TextFilterType::TYPE_IN:
            case TextFilterType::TYPE_NOT_IN:
                for ($i = 1; $i <= count($data['value']); $i++) {
                    $parameterNames[] = $ds->generateParameterName($this->getName());
                }

                break;
            default:
                $parameterNames[] = $ds->generateParameterName($this->getName());
        }

        $this->setCaseSensitivity($ds);
        $expr = $this->buildComparisonExpr(
            $ds,
            $comparisonType,
            $fieldName,
            $parameterNames
        );
        $this->resetCaseSensitivity($ds);

        if ($this->isValueRequired($comparisonType)) {
            switch ($data['type']) {
                case TextFilterType::TYPE_IN:
                case TextFilterType::TYPE_NOT_IN:
                    foreach ($parameterNames as $index => $name) {
                        $ds->setParameter($name, $this->convertValue($data['value'][$index]));
                    }

                    break;
                default:
                    $ds->setParameter(array_shift($parameterNames), $this->convertValue($data['value']));
            }
        }

        return $expr;
    }

    /**
     * @param string|array $parameterNames
     */
    #[\Override]
    protected function buildComparisonExpr(
        FilterDatasourceAdapterInterface $ds,
        $comparisonType,
        $fieldName,
        $parameterNames
    ) {
        switch ($comparisonType) {
            case TextFilterType::TYPE_IN:
                $expressionParts = [];
                foreach ($parameterNames as $name) {
                    QueryBuilderUtil::checkField($fieldName);
                    QueryBuilderUtil::checkParameter($name);
                    $expressionParts[] = $ds->expr()->like($fieldName, $name, true);
                }

                return $ds->expr()->orX(
                    ...$expressionParts
                );
            case TextFilterType::TYPE_NOT_IN:
                $expressionParts = [];
                foreach ($parameterNames as $name) {
                    QueryBuilderUtil::checkField($fieldName);
                    QueryBuilderUtil::checkParameter($name);
                    $expressionParts[] = $ds->expr()->notLike($fieldName, $name, true);
                }

                return $ds->expr()->andX(
                    ...$expressionParts
                );
            default:
                return parent::buildComparisonExpr(
                    $ds,
                    $comparisonType,
                    $fieldName,
                    array_shift($parameterNames)
                );
        }
    }
}
