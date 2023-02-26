<?php

namespace Oro\Bundle\WebsiteSearchBundle\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SearchBundle\Datagrid\Filter\SearchEnumFilter;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\EnumIdPlaceholder;

/**
 * The filter by a multi-enum entity for a datasource based on a search index.
 */
class SearchMultiEnumFilter extends SearchEnumFilter
{
    /**
     * {@inheritDoc}
     */
    protected function applyRestrictions(FilterDatasourceAdapterInterface $ds, array $data): bool
    {
        $fieldName = $this->get(FilterUtility::DATA_NAME_KEY);
        $criteria = Criteria::create();
        $builder = Criteria::expr();
        $placeholder = new EnumIdPlaceholder();

        foreach ($data['value'] as $value) {
            $criteria->orWhere(
                $builder->exists(
                    $placeholder->replace($fieldName, [EnumIdPlaceholder::NAME => $value])
                )
            );
        }

        $ds->addRestriction($criteria->getWhereExpression(), FilterUtility::CONDITION_AND);

        return true;
    }
}
