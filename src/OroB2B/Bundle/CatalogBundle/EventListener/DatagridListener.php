<?php

namespace OroB2B\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class DatagridListener
{
    const CATEGORY_COLUMN = 'category_name';

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
        // select
        $categoryTitleSelect = 'categoryTitle.string as ' . self::CATEGORY_COLUMN;
        $this->addConfigElement($config, '[source][query][select]', $categoryTitleSelect);

        // joins
        $joinCategory = [
            'join' => 'OroB2BCatalogBundle:Category',
            'alias' => 'productCategory',
            'conditionType' => 'WITH',
            'condition' => 'product MEMBER OF productCategory.products'
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

        // sorter
        $categorySorter = ['data_name' => self::CATEGORY_COLUMN];
        $this->addConfigElement($config, '[sorters][columns]', $categorySorter, self::CATEGORY_COLUMN);

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
