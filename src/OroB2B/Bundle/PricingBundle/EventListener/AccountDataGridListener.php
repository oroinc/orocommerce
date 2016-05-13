<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class AccountDataGridListener
{
    const PRICE_LIST_KEY = 'price_list';

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $grid = $event->getDatagrid();
        $config = $grid->getConfig();
        $this->addPriceListFilter($config);

        if ($this->isPriceListFilterEnabled($grid)) {
            $this->addPriceListCondition($grid);
        }
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function addPriceListFilter(DatagridConfiguration $config)
    {
        $config->addFilter(
            self::PRICE_LIST_KEY,
            [
                'label' => 'orob2b.pricing.pricelist.entity_label',
                'type' => 'entity',
                'data_name' => 'price_list',
                'options' => [
                    'field_type' => 'entity',
                    'field_options' => [
                        'multiple' => true,
                        'class' => PriceList::class,//todo
                        'property' => 'name'
                    ]
                ]
            ]
        );
    }

    /**
     * @param DatagridInterface $grid
     * @return bool
     */
    protected function isPriceListFilterEnabled(DatagridInterface $grid)
    {
        $params = $grid->getParameters();

        // todo: refactor
        if ($params->has('_minified')) {
            $filters = $params->get('_minified')['f'];
        } else {
            $filters = $params->get('_filter');
        }

        return $filters && array_key_exists(self::PRICE_LIST_KEY, $filters);
    }

    /**
     * @param DatagridInterface $grid
     */
    protected function addPriceListCondition(DatagridInterface $grid)
    {
        $params = $grid->getParameters();


        // todo: refactor
        if ($params->has('_minified')) {
            $filters = $params->get('_minified')['f'];
        } else {
            $filters = $params->get('_filter');
        }

        $priceLists = $filters[self::PRICE_LIST_KEY]['value'];
        unset($filters[self::PRICE_LIST_KEY]);
        if ($params->has('_minified')) {
            $minified = $params->get('_minified');
            $minified['f'] = $filters;
            $params->set('_minified', $minified);
        } else {
            $params->set('_filter', $filters);
        }

        $relationClass = 'OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount';

        $conditionFormat = 'EXISTS (SELECT 1 FROM %s r WHERE r.account = account AND IDENTITY(r.priceList) in (%s))';
        $condition = sprintf($conditionFormat, $relationClass, join(', ', $priceLists));
        $grid->getConfig()->offsetAddToArrayByPath('[source][query][where][and]', [$condition]);
    }
}
