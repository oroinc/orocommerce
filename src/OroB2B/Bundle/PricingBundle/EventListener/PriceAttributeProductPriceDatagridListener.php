<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceAttributePriceListRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceAttributeProductPriceRepository;

use Doctrine\ORM\Query\Expr;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;

class PriceAttributeProductPriceDatagridListener
{
    /**
     * @var PriceListRequestHandler
     */
    protected $priceListRequestHandler;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var PriceList
     */
    protected $priceList;

    /**
     * @var array
     */
    protected $attributesWithCurrencies;

    /**
     * @param PriceListRequestHandler $priceListRequestHandler
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        PriceListRequestHandler $priceListRequestHandler,
        DoctrineHelper $doctrineHelper
    ) {
        $this->priceListRequestHandler = $priceListRequestHandler;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $currencies = $this->priceListRequestHandler->getPriceListSelectedCurrencies($this->getPriceList());
        if (!$currencies) {
            return;
        }
        /** @var PriceAttributePriceListRepository $priceAttributePriceList */
        $priceAttributePriceList = $this->doctrineHelper
            ->getEntityRepository('OroB2BPricingBundle:PriceAttributePriceList');
        $this->attributesWithCurrencies = $priceAttributePriceList->getAttributesWithCurrencies($currencies);
        $config = $event->getConfig();
        foreach ($this->attributesWithCurrencies as $attributeWithCurrency) {
            $this->addProductPriceCurrencyColumn(
                $config,
                $attributeWithCurrency['currency'],
                $attributeWithCurrency['id'],
                $attributeWithCurrency['name']
            );
        }
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        $currencies = $this->priceListRequestHandler->getPriceListSelectedCurrencies($this->getPriceList());
        if (!$currencies) {
            return;
        }

        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        $prices = $this->getPrices($records);
        $groupedPrices = $this->groupPrices($prices);

        foreach ($records as $record) {
            $productId = $record->getValue('id');
            $priceContainer = [];
            foreach ($this->attributesWithCurrencies as $attributeWithCurrency) {
                $priceAttributeId = $attributeWithCurrency['id'];
                $currency = $attributeWithCurrency['currency'];
                $priceColumn = $this->buildColumnName($currency, $priceAttributeId);
                if (isset($groupedPrices[$productId][$priceAttributeId][$currency])) {
                    $priceContainer[$priceColumn] = $groupedPrices[$productId][$priceAttributeId][$currency];
                } else {
                    $priceContainer[$priceColumn] = [];
                }
            }
            if ($priceContainer) {
                $record->addData($priceContainer);
            }
        }
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $currency
     * @param integer $priceAttributeId
     * @param string $priceAttributeName
     */
    protected function addProductPriceCurrencyColumn(
        DatagridConfiguration $config,
        $currency,
        $priceAttributeId,
        $priceAttributeName
    ) {
        $columnName = $this->buildColumnName($currency, $priceAttributeId);
        $joinAlias = $this->buildJoinAlias($columnName);

        $selectPattern = 'min(%s.value) as %s';
        $config->offsetAddToArrayByPath('[source][query][select]', [sprintf($selectPattern, $joinAlias, $columnName)]);

        $this->addConfigProductPriceJoin($config, $currency, $priceAttributeId);

        $column = $this->createPriceColumn($currency, $priceAttributeName);
        $config->offsetAddToArrayByPath('[columns]', [$columnName => $column]);
        $config->offsetAddToArrayByPath('[sorters][columns]', [$columnName => ['data_name' => $columnName]]);

        $this->addConfigFilter($config, $currency, $priceAttributeId);
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $currency
     * @param integer $priceAttributeId
     */
    protected function addConfigFilter(
        DatagridConfiguration $config,
        $currency,
        $priceAttributeId
    ) {
        $columnName = $this->buildColumnName($currency, $priceAttributeId);
        $filter = ['type' => 'price-attribute-product-price', 'data_name' => $currency];

        $config->offsetAddToArrayByPath('[filters][columns]', [$columnName => $filter]);
    }

    /**
     * @param string $currencyIsoCode
     * @param integer $priceAttributeId
     * @return string
     */
    protected function buildColumnName($currencyIsoCode, $priceAttributeId)
    {
        return 'price_attribute_price_column_'.strtolower($currencyIsoCode).'_'.$priceAttributeId;
    }

    /**
     * @param string $currency
     * @param string $priceAttributeName
     * @return array
     */
    protected function createPriceColumn($currency, $priceAttributeName)
    {
        return [
            'label' => sprintf('%s(%s)', $priceAttributeName, $currency),
            'type' => 'twig',
            'template' => 'OroB2BPricingBundle:Datagrid:Column/productPrice.html.twig',
            'frontend_type' => 'html',
            'renderable' => true,
        ];
    }

    /**
     * @param ResultRecord[] $records
     * @return ProductPrice[]
     */
    protected function getPrices(array $records)
    {
        /** @var PriceAttributeProductPriceRepository $priceRepository */
        $priceRepository = $this->doctrineHelper->getEntityRepository('OroB2BPricingBundle:PriceAttributeProductPrice');

        $productIds = array_map(
            function (ResultRecord $record) {
                return $record->getValue('id');
            },
            $records
        );
        $priceAttributeProductPriceIds = [];
        foreach ($this->attributesWithCurrencies as $attributeWithCurrency) {
            $priceAttributeProductPriceIds[] = $attributeWithCurrency['id'];
        }

        return $priceRepository->findByPriceAttributeProductPriceIdsAndProductIds(
            $priceAttributeProductPriceIds,
            $productIds
        );
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $currency
     * @param integer $priceAttributeId
     */
    protected function addConfigProductPriceJoin(DatagridConfiguration $config, $currency, $priceAttributeId)
    {
        $columnName = $this->buildColumnName($currency, $priceAttributeId);
        $joinAlias = $this->buildJoinAlias($columnName);
        $expr = new Expr();
        $joinExpr = $expr
            ->andX(sprintf('%s.product = product.id', $joinAlias))
            ->add($expr->eq(sprintf('%s.currency', $joinAlias), $expr->literal($currency)))
            ->add($expr->eq(sprintf('%s.priceList', $joinAlias), $expr->literal($priceAttributeId)))
            ->add($expr->eq(sprintf('%s.quantity', $joinAlias), 1));

        $config->offsetAddToArrayByPath(
            '[source][query][join][left]',
            [
                [
                    'join' => 'OroB2BPricingBundle:PriceAttributeProductPrice',
                    'alias' => $joinAlias,
                    'conditionType' => Expr\Join::WITH,
                    'condition' => (string)$joinExpr,
                ],
            ]
        );
    }

    /**
     * @param string $columnName
     * @return string
     */
    protected function buildJoinAlias($columnName)
    {
        return $columnName.'_table';
    }

    /**
     * @return PriceList
     */
    protected function getPriceList()
    {
        if (!$this->priceList) {
            $this->priceList = $this->priceListRequestHandler->getPriceList();
        }

        return $this->priceList;
    }
    
    /**
     * @param array $attributesWithCurrencies
     * @return $this
     */
    public function setAttributesWithCurrencies(array $attributesWithCurrencies)
    {
        $this->attributesWithCurrencies = $attributesWithCurrencies;

        return $this;
    }
    
    /**
     * @param PriceAttributeProductPrice[] $prices
     * @return array
     */
    protected function groupPrices(array $prices)
    {
        $groupedPrices = [];
        foreach ($prices as $price) {
            $productId = $price->getProduct()->getId();

            $currencyIsoCode = $price->getPrice()->getCurrency();
            $priceAttributeId = $price->getPriceList()->getId();
            if (!isset($groupedPrices[$productId][$priceAttributeId][$currencyIsoCode])) {
                $groupedPrices[$productId][$priceAttributeId][$currencyIsoCode] = [];
            }
            $groupedPrices[$productId][$priceAttributeId][$currencyIsoCode][] = $price;
        }

        return $groupedPrices;
    }
}
