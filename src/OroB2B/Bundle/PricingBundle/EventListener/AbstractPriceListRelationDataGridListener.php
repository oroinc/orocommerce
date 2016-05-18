<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\PricingBundle\Filter\PriceListsFilter;

abstract class AbstractPriceListRelationDataGridListener
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
        $this->addPriceListColumn($config);
        $this->addPriceListFilter($config);
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        $priceListHoldersIds = [];

        foreach ($records as $record) {
            $priceListHoldersIds[] = $record->getValue('id');
        }

        if (count($priceListHoldersIds) > 0) {
            $groupedPriceLists = [];
            $relations = $this->getRelations($priceListHoldersIds);
            foreach ($relations as $relation) {
                $websiteId = $relation->getWebsite()->getId();
                $relationId = $this->getObjectId($relation);
                $groupedPriceLists[$relationId][$websiteId]['website'] = $relation->getWebsite();
                $groupedPriceLists[$relationId][$websiteId]['priceLists'][] = $relation->getPriceList();
            }

            foreach ($records as $record) {
                $priceListHolderId = $record->getValue('id');
                $priceLists = [];
                if (array_key_exists($priceListHolderId, $groupedPriceLists)) {
                    $priceLists = $groupedPriceLists[$priceListHolderId];
                }
                $data = ['price_lists' => $priceLists];
                $record->addData($data);
            }
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
                'type' => 'price-lists',
                'data_name' => 'price_list',
                PriceListsFilter::RELATION_CLASS_NAME_PARAMETER => $this->getRelationClassName(),
                'options' => [
                    'field_type' => 'entity',
                    'field_options' => [
                        'multiple' => false,
                        'class' => 'OroB2B\Bundle\PricingBundle\Entity\PriceList',
                        'property' => 'name'
                    ]
                ]
            ]
        );
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function addPriceListColumn(DatagridConfiguration $config)
    {
        $config->addColumn(
            'price_lists',
            [
                'label' => 'orob2b.pricing.pricelist.entity_plural_label',
                'type' => 'twig',
                'template' => 'OroB2BPricingBundle:Datagrid:Column/priceLists.html.twig',
                'frontend_type' => 'html',
                'renderable' => false,
            ]
        );
    }

    /**
     * @param array|int[] $priceListHolderIds
     * @return BasePriceListRelation[]
     */
    abstract protected function getRelations(array $priceListHolderIds);

    /**
     * @param BasePriceListRelation $relation
     * @return int
     */
    abstract protected function getObjectId(BasePriceListRelation $relation);

    /**
     * @return string
     */
    abstract protected function getRelationClassName();
}
