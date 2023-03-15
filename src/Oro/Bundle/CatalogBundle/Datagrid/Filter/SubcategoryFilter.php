<?php

namespace Oro\Bundle\CatalogBundle\Datagrid\Filter;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Placeholder\CategoryPathPlaceholder;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\AbstractFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchEntityFilterType;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Component\Exception\UnexpectedTypeException;

/**
 * The filter by a subcategory value for a datasource based on a search index.
 */
class SubcategoryFilter extends AbstractFilter
{
    public const FILTER_TYPE_NAME = 'subcategory';

    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FORM_OPTIONS_KEY]['class'] = Category::class;

        parent::init($name, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();

        $formView = $this->getFormView();
        $fieldView = $formView->children['value'];

        $metadata['choices'] = $fieldView->vars['choices'];

        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function prepareData(array $data): array
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof SearchFilterDatasourceAdapter) {
            throw new UnexpectedTypeException($ds, SearchFilterDatasourceAdapter::class);
        }

        return $this->applyRestrictions($ds, $data);
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormType(): string
    {
        return SearchEntityFilterType::class;
    }

    protected function applyRestrictions(FilterDatasourceAdapterInterface $ds, array $data): bool
    {
        /** @var Category $rootCategory */
        $rootCategory = $this->get('rootCategory');

        /** @var Category[] $categories */
        $categories = $data['value']->toArray();
        if (!$categories) {
            $categories = [$rootCategory];
        }

        $placeholder = new CategoryPathPlaceholder();
        $fieldName = $this->getFieldName();

        $criteria = Criteria::create();
        $builder = Criteria::expr();

        foreach ($categories as $category) {
            $categoryFieldName = $placeholder->replace(
                $fieldName,
                [CategoryPathPlaceholder::NAME => $category->getMaterializedPath()]
            );

            $criteria->orWhere(
                $builder->exists($categoryFieldName)
            );
        }

        $ds->addRestriction($criteria->getWhereExpression(), FilterUtility::CONDITION_AND);

        return true;
    }

    protected function getFieldName(): string
    {
        return sprintf(
            '%s.%s.%s',
            Query::TYPE_INTEGER,
            $this->get(FilterUtility::DATA_NAME_KEY),
            CategoryPathPlaceholder::NAME
        );
    }
}
