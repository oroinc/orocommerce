<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class DatagridListener
{
    const PRICE_COLUMN = 'price_list_name';

    /**
     * @var string
     */
    protected $priceListClass;

    /**
     * @param string $priceListClass
     */
    public function setPriceListClass($priceListClass)
    {
        $this->priceListClass = $priceListClass;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBeforeCustomers(BuildBefore $event)
    {
        $this->addPriceListRelation($event->getConfig(), 'customer MEMBER OF priceList.customers');
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBeforeCustomerGroups(BuildBefore $event)
    {
        $this->addPriceListRelation($event->getConfig(), 'customer_group MEMBER OF priceList.customerGroups');
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $joinCondition
     */
    protected function addPriceListRelation(DatagridConfiguration $config, $joinCondition)
    {
        // select
        $select = 'priceList.name as ' . self::PRICE_COLUMN;
        $this->addConfigElement($config, '[source][query][select]', $select);

        // left join
        $leftJoin = [
            'join' => $this->priceListClass,
            'alias' => 'priceList',
            'conditionType' => 'WITH',
            'condition' => $joinCondition
        ];
        $this->addConfigElement($config, '[source][query][join][left]', $leftJoin);

        // column
        $column = ['label' => 'orob2b.pricing.pricelist.entity_label'];
        $this->addConfigElement($config, '[columns]', $column, self::PRICE_COLUMN);

        // sorter
        $sorter = ['data_name' => self::PRICE_COLUMN];
        $this->addConfigElement($config, '[sorters][columns]', $sorter, self::PRICE_COLUMN);

        // filter
        $filter = [
            'type' => 'entity',
            'data_name' => 'priceList.id',
            'options' => [
                'field_type' => 'entity',
                'field_options' => [
                    'class' => $this->priceListClass,
                    'property' => 'name',
                ]
            ]
        ];
        $this->addConfigElement($config, '[filters][columns]', $filter, self::PRICE_COLUMN);
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
