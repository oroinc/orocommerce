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
        $select = 'categoryTitle.string as ' . self::CATEGORY_COLUMN;
        $this->addConfigElement($config, '[source][query][select]', $select);

        // left joins
        $leftJoin1 = [
            'join' => 'OroB2BCatalogBundle:ProductCategory',
            'alias' => 'productCategory',
            'conditionType' => 'WITH',
            'condition' => 'product = productCategory.product'
        ];
        $this->addConfigElement($config, '[source][query][join][left]', $leftJoin1);

        $leftJoin2 = [
            'join' => 'productCategory.category',
            'alias' => 'category'
        ];
        $this->addConfigElement($config, '[source][query][join][left]', $leftJoin2);

        $leftJoin3 = [
            'join' => 'category.titles',
            'alias' => 'categoryTitle',
        ];
        $this->addConfigElement($config, '[source][query][join][left]', $leftJoin3);

        // where
        $where = 'categoryTitle.locale IS NULL';
        $this->addConfigElement($config, '[source][query][where][and]', $where);

        // column
        $column = ['label' => 'orob2b.catalog.category.entity_label'];
        $this->addConfigElement($config, '[columns]', $column, self::CATEGORY_COLUMN);

        // sorter
        $sorter = ['data_name' => self::CATEGORY_COLUMN];
        $this->addConfigElement($config, '[sorters][columns]', $sorter, self::CATEGORY_COLUMN);

        // filter
        $filter = [
            'type' => 'string',
            'data_name' => 'categoryTitle.string'
        ];
        $this->addConfigElement($config, '[filters][columns]', $filter, self::CATEGORY_COLUMN);
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $path
     * @param mixed $element
     * @param mixed $key
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
