<?php

namespace Oro\Bundle\CatalogBundle\Datagrid\Filter;

use Oro\Bundle\CatalogBundle\Form\Type\Filter\SubcategoryFilterType;
use Oro\Bundle\CatalogBundle\Placeholder\CategoryPathPlaceholder;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\AbstractFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;

class SubcategoryFilter extends AbstractFilter
{
    const FILTER_TYPE_NAME = 'subcategory';

    /**
     * {@inheritDoc}
     */
    protected function getFormType()
    {
        return SubcategoryFilterType::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();

        $formView = $this->getForm()->createView();
        $fieldView = $formView->children['value'];

        $metadata['categories'] = $fieldView->vars['choices'];

        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof SearchFilterDatasourceAdapter) {
            throw new \RuntimeException('Invalid filter datasource adapter provided: ' . get_class($ds));
        }

        return $this->applyRestrictions($ds, $data);
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param array $data
     *
     * @return bool
     */
    protected function applyRestrictions(FilterDatasourceAdapterInterface $ds, array $data)
    {
        $rootCategory = $this->get('rootCategory');
        $type = $data['type'];
        $builder = Criteria::expr();

        switch ($type) {
            case SubcategoryFilterType::TYPE_NOT_INCLUDE:
                $ds->addRestriction(
                    $builder->eq(
                        $this->getFieldName($type),
                        $rootCategory->getMaterializedPath()
                    ),
                    FilterUtility::CONDITION_AND
                );

                return true;
                break;
            case SubcategoryFilterType::TYPE_INCLUDE:
                $categories = $data['value']->toArray();

                if (count($categories) === 0) {
                    $categories = [$rootCategory];
                }

                $criteria = Criteria::create();

                $placeholder = new CategoryPathPlaceholder();
                foreach ($categories as $category) {
                    $fieldName = $placeholder->replace(
                        $this->getFieldName($type),
                        [CategoryPathPlaceholder::NAME => $category->getMaterializedPath()]
                    );

                    $criteria->orWhere(
                        $builder->eq($fieldName, 1)
                    );
                }

                $ds->addRestriction($criteria->getWhereExpression(), FilterUtility::CONDITION_AND);

                return true;
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * @param int $type
     *
     * @return string
     */
    protected function getFieldName($type)
    {
        $source = Query::TYPE_TEXT;
        $dataName = $this->get(FilterUtility::DATA_NAME_KEY);
        $postfix = '';

        if ($type === SubcategoryFilterType::TYPE_INCLUDE) {
            $source = Query::TYPE_INTEGER;
            $postfix = '_' . CategoryPathPlaceholder::NAME;
        }

        return sprintf('%s.%s%s', $source, $dataName, $postfix);
    }
}
