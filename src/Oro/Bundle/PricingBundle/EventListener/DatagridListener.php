<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class DatagridListener
{
    const PRICE_COLUMN = 'price_list_name';

    /**
     * @param BuildBefore $event
     */
    public function onBuildBeforeAccounts(BuildBefore $event)
    {
        $leftJoins = [
            [
                'join' => 'Oro\Bundle\PricingBundle\Entity\PriceListToAccount',
                'alias' => 'priceListToAccount',
                'conditionType' => 'WITH',
                'condition' => 'priceListToAccount.account = account',
            ],
            [
                'join' => 'priceListToAccount.priceList',
                'alias' => 'priceList',
                'conditionType' => 'WITH',
                'condition' => 'priceListToAccount.priceList = priceList',
            ],
        ];
        $this->addPriceListRelation($event->getConfig(), $leftJoins);
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBeforeAccountGroups(BuildBefore $event)
    {
        $leftJoins = [
            [
                'join' => 'Oro\Bundle\PricingBundle\Entity\PriceListToAccountGroup',
                'alias' => 'priceListToAccountGroup',
                'conditionType' => 'WITH',
                'condition' => 'priceListToAccountGroup.accountGroup = account_group',
            ],
            [
                'join' => 'priceListToAccountGroup.priceList',
                'alias' => 'priceList',
                'conditionType' => 'WITH',
                'condition' => 'priceListToAccountGroup.priceList = priceList',
            ],
        ];
        $this->addPriceListRelation($event->getConfig(), $leftJoins);
    }

    /**
     * @param DatagridConfiguration $config
     * @param array $leftJoins
     */
    protected function addPriceListRelation(DatagridConfiguration $config, $leftJoins)
    {
        // select
        $select = 'priceList.name as ' . self::PRICE_COLUMN;
        $this->addConfigElement($config, '[source][query][select]', $select);

        // left join
        foreach ($leftJoins as $leftJoin) {
            $this->addConfigElement($config, '[source][query][join][left]', $leftJoin);
        }

        // column
        $column = ['label' => 'oro.pricing.pricelist.entity_label'];
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
                    'class' => 'Oro\Bundle\PricingBundle\Entity\PriceList',
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
