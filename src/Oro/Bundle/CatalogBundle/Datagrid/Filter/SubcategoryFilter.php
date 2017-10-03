<?php

namespace Oro\Bundle\CatalogBundle\Datagrid\Filter;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Placeholder\CategoryPathPlaceholder;
use Oro\Bundle\CatalogBundle\Provider\SubcategoryProvider;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Symfony\Component\Form\FormFactoryInterface;

class SubcategoryFilter extends EntityFilter
{
    const FILTER_TYPE_NAME = 'subcategory';

    /** @var SubcategoryProvider */
    protected $categoryProvider;

    /**
     * @param FormFactoryInterface $factory
     * @param FilterUtility $util
     * @param SubcategoryProvider $categoryProvider
     */
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        SubcategoryProvider $categoryProvider
    ) {
        parent::__construct($factory, $util);

        $this->categoryProvider = $categoryProvider;
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormType()
    {
        return EntityFilterType::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        parent::init($name, $params);

        //TODO: Configuration of filter should be not in filter class
        $categories = array_filter(
            $this->categoryProvider->getAvailableSubcategories(),
            function (Category $item) {
                return count($item->getProducts()) > 0;
            }
        );

        $this->params['enabled'] = count($categories) > 0;
        $this->params['options']['field_options']['multiple'] = true;
        $this->params['options']['field_options']['class'] = Category::class;
        $this->params['options']['field_options']['choices'] = $categories;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof SearchFilterDatasourceAdapter) {
            throw new \RuntimeException('Invalid filter datasource adapter provided: ' . get_class($ds));
        }

        return $this->applyRestrictions($ds, $data['value']->toArray());
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param array $categories
     *
     * @return bool
     */
    protected function applyRestrictions(FilterDatasourceAdapterInterface $ds, array $categories)
    {
        $expr = Criteria::expr();

        $placeholder = new CategoryPathPlaceholder();
        $sourceField = $this->get(FilterUtility::DATA_NAME_KEY);

        if (!$categories) {
            //TODO: filter should be configured from outside and it shouldn't aware of external dependency like that
            $currentCategory = $this->categoryProvider->getCurrentCategory();

            $fieldName = $placeholder->replace(
                $sourceField,
                [CategoryPathPlaceholder::NAME => $currentCategory->getMaterializedPath()]
            );

            $ds->addRestriction(
                $expr->eq($fieldName, 1),
                FilterUtility::CONDITION_AND
            );

            return true;
        }

        $criteria = Criteria::create();

        foreach ($categories as $category) {
            $path = $category->getMaterializedPath();
            $fieldName = $placeholder->replace($sourceField, [CategoryPathPlaceholder::NAME => $path]);

            $criteria->orWhere(
                $expr->eq($fieldName, 1)
            );
        }

        $ds->addRestriction($criteria->getWhereExpression(), FilterUtility::CONDITION_AND);

        return true;
    }
}
