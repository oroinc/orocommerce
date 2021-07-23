<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Filter\PriceListsFilter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Adds price list column and filter
 * Adds price list data to selected records
 */
abstract class AbstractPriceListRelationDataGridListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    const PRICE_LIST_KEY = 'price_list';

    /**
     * @var Registry
     */
    protected $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function onBuildBefore(BuildBefore $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $grid = $event->getDatagrid();
        $config = $grid->getConfig();
        $this->addPriceListColumn($config);
        $this->addPriceListFilter($config);
    }

    public function onResultAfter(OrmResultAfter $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

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

    protected function addPriceListFilter(DatagridConfiguration $config)
    {
        $config->addFilter(
            self::PRICE_LIST_KEY,
            [
                'label' => 'oro.pricing.pricelist.entity_label',
                'type' => 'price-lists',
                'data_name' => 'price_list',
                PriceListsFilter::RELATION_CLASS_NAME_PARAMETER => $this->getRelationClassName(),
                'options' => [
                    'field_type' => EntityType::class,
                    'field_options' => [
                        'multiple' => false,
                        'class' => 'Oro\Bundle\PricingBundle\Entity\PriceList',
                        'choice_label' => 'name'
                    ]
                ]
            ]
        );
    }

    protected function addPriceListColumn(DatagridConfiguration $config)
    {
        $config->addColumn(
            'price_lists',
            [
                'label' => 'oro.pricing.pricelist.entity_plural_label',
                'type' => 'twig',
                'template' => 'OroPricingBundle:Datagrid:Column/priceLists.html.twig',
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
