<?php

namespace OroB2B\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class DatagridListener
{
    const CATEGORY_COLUMN = 'category_name';
    const IN_CATEGORY_COLUMN = 'in_category';

    /**
     * @param BuildBefore $event
     */
    public function onBuildBeforeProductsSelect(BuildBefore $event)
    {
        $this->addCategoryRelation($event->getConfig());
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function addCategoryRelation(DatagridConfiguration $config)
    {
        // bind params
        $this->addConfigElement($config, '[source][bind_parameters]', ['name' => 'category_id']);

        // select
        $categoryTitleSelect = 'categoryTitle.string as ' . self::CATEGORY_COLUMN;
        $this->addConfigElement($config, '[source][query][select]', $categoryTitleSelect);

        $inCategorySelect = "(
                                CASE WHEN (:category_id IS NOT NULL) THEN
                                    CASE WHEN
                                                product MEMBER OF productCategory.products
                                                OR product.id IN (:data_in) AND product.id NOT IN (:data_not_in)
                                        THEN true ELSE false END
                                ELSE
                                    CASE WHEN product.id IN (:data_in) AND product.id NOT IN (:data_not_in)
                                    THEN true ELSE false END
                                END
                              ) as " . self::IN_CATEGORY_COLUMN;

        $this->addConfigElement($config, '[source][query][select]', $inCategorySelect);

        // joins
        $joinCategory = [
            'join' => 'OroB2BCatalogBundle:Category',
            'alias' => 'productCategory',
            'conditionType' => 'WITH',
            'condition' => 'productCategory = :category_id'
        ];
        $this->addConfigElement($config, '[source][query][join][left]', $joinCategory);

        $joinCategoryTitles = [
            'join' => 'productCategory.titles',
            'alias' => 'categoryTitle',
        ];
        $this->addConfigElement($config, '[source][query][join][left]', $joinCategoryTitles);

        // conditions
        $where = 'categoryTitle.locale IS NULL';
        $this->addConfigElement($config, '[source][query][where][and]', $where);

        // columns
        $categoryColumn = ['label' => 'orob2b.catalog.category.entity_label'];
        $this->addConfigElement($config, '[columns]', $categoryColumn, self::CATEGORY_COLUMN);

        $inCategoryColumn = [
            'label' => 'orob2b.catalog.product.in_category.label',
            'frontend_type' => 'boolean',
            'editable' => true
        ];

        $this->addConfigElement($config, '[columns]', $inCategoryColumn, self::IN_CATEGORY_COLUMN);

        $inCategorySelection = [
            'dataField' => 'id',
            'columnName' => self::IN_CATEGORY_COLUMN,
            'selectors' =>
                [
                    'included' => '#categoryAppendProducts',
                    'excluded' => '#categoryRemoveProducts'
                ]
        ];
        $this->addConfigElement($config, '[options]', $inCategorySelection, 'rowSelection');

        // sorter
        $categorySorter = ['data_name' => self::CATEGORY_COLUMN];
        $this->addConfigElement($config, '[sorters][columns]', $categorySorter, self::CATEGORY_COLUMN);

        $inCategorySorter = ['data_name' => self::IN_CATEGORY_COLUMN];
        $this->addConfigElement($config, '[sorters][columns]', $inCategorySorter, self::IN_CATEGORY_COLUMN);

        // filter
        $categoryFilter = [
            'type' => 'string',
            'data_name' => 'categoryTitle.string'
        ];
        $this->addConfigElement($config, '[filters][columns]', $categoryFilter, self::CATEGORY_COLUMN);
    }

    /**
     * @param DatagridConfiguration $config
     * @param string                $path
     * @param mixed                 $element
     * @param mixed                 $key
     */
    protected function addConfigElement(DatagridConfiguration $config, $path, $element, $key = null)
    {
        $select = $config->offsetGetByPath($path);
        if ($key) {
            $select[$key] = $element;
        } else {
            $select[] = $element;
        }
        $config->offsetSetByPath($path, $select);
    }
}
