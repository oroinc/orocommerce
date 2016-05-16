<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;

abstract class AbstractPriceListRelationDataGridListener
{
    const PRICE_LIST_KEY = 'price_list';

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param TranslatorInterface $translator
     */
    protected $translator;

    /**
     * @param Registry $registry
     * @param TranslatorInterface $translator
     */
    public function __construct(Registry $registry, TranslatorInterface $translator)
    {
        $this->registry = $registry;
        $this->translator = $translator;
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

        if ($this->isPriceListFilterEnabled($grid)) {
            $this->addPriceListCondition($grid);
        }
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
            $relations = $groupedPriceLists = [];

            $this->getRelations($accountIds);
            foreach ($relations as $relation) {
                $groupedPriceLists[$relation->getAccount()->getId()][$relation->getWebsite()->getId()]['website']
                    = $relation->getWebsite();
                $groupedPriceLists[$relation->getAccount()->getId()][$relation->getWebsite()->getId()]['priceLists'][]
                    = $relation->getPriceList();
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
                'label' => $this->translator->trans('orob2b.pricing.pricelist.entity_plural_label'),
                'type' => 'twig',
                'template' => 'OroB2BPricingBundle:Datagrid:Column/priceLists.html.twig',
                'frontend_type' => 'html',
                'renderable' => false,
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
    abstract protected function addPriceListCondition(DatagridInterface $grid);

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
}
