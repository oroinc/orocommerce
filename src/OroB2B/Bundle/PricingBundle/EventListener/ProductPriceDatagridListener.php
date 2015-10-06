<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Doctrine\ORM\Query\Expr;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Model\AbstractPriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class ProductPriceDatagridListener
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var AbstractPriceListRequestHandler
     */
    protected $priceListRequestHandler;

    /**
     * @var string
     */
    protected $priceListClass;

    /**
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     * @param AbstractPriceListRequestHandler $priceListRequestHandler
     */
    public function __construct(
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        AbstractPriceListRequestHandler $priceListRequestHandler
    ) {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->priceListRequestHandler = $priceListRequestHandler;
    }

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
    public function onBuildBefore(BuildBefore $event)
    {
        $currencies = $this->getCurrencies();
        if (!$currencies) {
            return;
        }

        $config = $event->getConfig();

        $units = $this->getAllUnits();
        foreach ($currencies as $currencyIsoCode) {
            $columnName = $this->buildColumnName($currencyIsoCode);
            $column = [
                'label' => $this->translator->trans(
                    'orob2b.pricing.productprice.price_in_%currency%',
                    ['%currency%' => $currencyIsoCode]
                ),
                'type' => 'twig',
                'template' => 'OroB2BPricingBundle:Datagrid:Column/productPrice.html.twig',
                'frontend_type' => 'html',
            ];

            $config->offsetSetByPath(sprintf('[columns][%s]', $columnName), $column);

            $filter = [
                'type' => 'product-price',
                'data_name' => $currencyIsoCode
            ];

            $config->offsetSetByPath(sprintf('[filters][columns][%s]', $columnName), $filter);

            // add prices for units
            foreach ($units as $unit) {
                $this->addProductPriceRelation($config, $unit, $currencyIsoCode);
            }
        }
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        $currencies = $this->getCurrencies();
        if (!$currencies) {
            return;
        }

        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $productIds = [];
        foreach ($records as $record) {
            $productIds[] = $record->getValue('id');
        }

        /** @var ProductPriceRepository $priceRepository */
        $priceRepository = $this->doctrineHelper->getEntityRepository('OroB2BPricingBundle:ProductPrice');

        $priceList = $this->getPriceList();
        $showTierPrices = $this->priceListRequestHandler->getShowTierPrices();
        $prices = $priceRepository->findByPriceListIdAndProductIds($priceList->getId(), $productIds, $showTierPrices);
        $groupedPrices = $this->groupPrices($prices);

        foreach ($records as $record) {
            $record->addData(['showTierPrices' => $showTierPrices]);

            $productId = $record->getValue('id');
            $priceContainer = [];
            foreach ($currencies as $currencyIsoCode) {
                $columnName = $this->buildColumnName($currencyIsoCode);
                if (isset($groupedPrices[$productId][$currencyIsoCode])) {
                    $priceContainer[$columnName] = $groupedPrices[$productId][$currencyIsoCode];
                } else {
                    $priceContainer[$columnName] = [];
                }
            }
            if ($priceContainer) {
                $record->addData($priceContainer);
            }
        }
    }

    /**
     * @param string $currencyIsoCode
     * @param string $unitCode
     * @return string
     */
    protected function buildColumnName($currencyIsoCode, $unitCode = null)
    {
        $result = 'price_column_' . strtolower($currencyIsoCode);

        return $unitCode ? sprintf('%s_%s', $result, strtolower($unitCode)) : $result;
    }

    /**
     * @return PriceList
     */
    protected function getPriceList()
    {
        return $this->priceListRequestHandler->getPriceList();
    }

    /**
     * @return array
     */
    protected function getCurrencies()
    {
        return $this->priceListRequestHandler->getPriceListSelectedCurrencies();
    }

    /**
     * @param ProductPrice[] $prices
     * @return array
     */
    protected function groupPrices(array $prices)
    {
        $groupedPrices = [];
        foreach ($prices as $price) {
            $productId = $price->getProduct()->getId();
            $currencyIsoCode = $price->getPrice()->getCurrency();
            if (!isset($groupedPrices[$productId][$currencyIsoCode])) {
                $groupedPrices[$productId][$currencyIsoCode] = [];
            }
            $groupedPrices[$productId][$currencyIsoCode][] = $price;
        }

        return $groupedPrices;
    }

    /**
     * @param DatagridConfiguration $config
     * @param ProductUnit $unit
     * @param $currency
     */
    protected function addProductPriceRelation(DatagridConfiguration $config, ProductUnit $unit, $currency)
    {
        $joinAlias = $this->buildColumnName($currency, $unit);
        $columnName = $joinAlias . '_value';
        $priceList = $this->getPriceList();
        // select
        $this->addConfigElement($config, '[source][query][select]', sprintf('%s.value as %s', $joinAlias, $columnName));
        $config->offsetSetByPath('[source][query][groupBy]', 'product.id');

        $expr = new Expr();
        $joinExpr = $expr
            ->andX(sprintf('%s.product = product.id', $joinAlias))
            ->add($expr->eq(sprintf('%s.currency', $joinAlias), $expr->literal($currency)))
            ->add($expr->eq(sprintf('%s.unit', $joinAlias), $expr->literal($unit)))
            ->add($expr->eq(sprintf('%s.priceList', $joinAlias), $expr->literal($priceList->getId())))
        ;

        // left join
        $leftJoin = [
            'join' => $this->priceListClass,
            'alias' => $joinAlias,
            'conditionType' => Expr\Join::WITH,
            'condition' => (string) $joinExpr,
        ];
        $this->addConfigElement($config, '[source][query][join][left]', $leftJoin);

        // column
        $column = [
            'label' => $this->translator->trans(
                'orob2b.pricing.productprice.price_%unit%_in_%currency%',
                [
                    '%currency%' => $currency,
                    '%unit%' =>  $unit->getCode(),
                ]
            ),
        ];

        $this->addConfigElement($config, '[columns]', $column, $columnName);

        // sorter
        $sorter = ['data_name' => $columnName];
        $this->addConfigElement($config, '[sorters][columns]', $sorter, $columnName);

        // filter
        $filter = [
            'type' => 'product-price',
            'data_name' => $columnName,
        ];

        $this->addConfigElement($config, '[filters][columns]', $filter, $columnName);
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

    /**
     * @return ProductUnit[]
     */
    protected function getAllUnits()
    {
        return $this->doctrineHelper
            ->getEntityRepository('OroB2BProductBundle:ProductUnit')
            ->findBy([], ['code' => 'ASC'])
        ;
    }
}
