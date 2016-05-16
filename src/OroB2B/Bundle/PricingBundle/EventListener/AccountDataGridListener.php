<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class AccountDataGridListener
{
    const PRICE_LIST_KEY = 'price_list';

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

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

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        $accountIds = [];

        foreach ($records as $record) {
            $accountIds[] = $record->getValue('id');
        }

        if (!empty($accountIds)) {
            $groupedPriceLists = [];
            $relations = $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListToAccount')
                ->getRepository('OroB2BPricingBundle:PriceListToAccount')
                ->getRelationsByAccounts($accountIds);

            foreach ($relations as $relation) {
                $groupedPriceLists[$relation->getAccount()->getId()][$relation->getWebsite()->getId()]['website']
                    = $relation->getWebsite();
                $groupedPriceLists[$relation->getAccount()->getId()][$relation->getWebsite()->getId()]['priceLists'][]
                    = $relation->getPriceList()->getName();
            }

            foreach ($records as $record) {
                $accountId = $record->getValue('id');
                $priceLists = [];
                if (array_key_exists($accountId, $groupedPriceLists)) {
                    $priceLists = $groupedPriceLists[$accountId];
                }
                $data = ['price_lists' => $priceLists];
                $record->addData($data);
            }
        }
    }
}
