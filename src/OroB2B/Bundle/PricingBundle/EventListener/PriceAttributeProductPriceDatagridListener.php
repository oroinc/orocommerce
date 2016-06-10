<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceAttributePriceListRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceAttributeProductPriceRepository;
use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\ORM\Query\Expr;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class PriceAttributeProductPriceDatagridListener
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

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
     * @param TranslatorInterface $translator
     * @param PriceListRequestHandler $priceListRequestHandler
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        TranslatorInterface $translator,
        PriceListRequestHandler $priceListRequestHandler,
        DoctrineHelper $doctrineHelper
    ) {
        $this->translator = $translator;
        $this->priceListRequestHandler = $priceListRequestHandler;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
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
        // add prices for currencies
        foreach ($this->attributesWithCurrencies as $attributeWithCurrency) {
            $this->addProductPriceCurrencyColumn(
                $config,
                $attributeWithCurrency['currency'],
                $attributeWithCurrency['id']
            );
        }

        $units = $this->getAllUnits();
        foreach ($this->attributesWithCurrencies as $attributeWithCurrency) {
            // add prices for units
            foreach ($units as $unit) {
                $this->addProductPriceCurrencyColumn(
                    $config,
                    $attributeWithCurrency['currency'],
                    $attributeWithCurrency['id'],
                    $unit
                );
            }
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
        $units = $this->getAllUnits();

        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        $prices = $this->getPrices($records);
        $pricesByUnits = $this->getPricesByUnits($prices);
        $groupedPrices = $this->groupPrices($prices);

        foreach ($records as $record) {
//            $record->addData(['showTierPrices' => $showTierPrices]);

            $productId = $record->getValue('id');
            $priceContainer = [];
            foreach ($this->attributesWithCurrencies as $attributeWithCurrency) {
                foreach ($units as $unit) {
                    $priceUnitColumn = $this->buildColumnName(
                        $attributeWithCurrency['currency'],
                        $attributeWithCurrency['id'],
                        $unit
                    );

                    $data = [$priceUnitColumn => []];
                    if (isset($pricesByUnits[$productId][$attributeWithCurrency['currency']][$unit->getCode()])) {
                        $data = [
                            $priceUnitColumn =>
                                $pricesByUnits[$productId][$attributeWithCurrency['currency']][$unit->getCode()],
                        ];
                    }

                    $record->addData($data);
                }

                $priceColumn = $this->buildColumnName(
                    $attributeWithCurrency['currency'],
                    $attributeWithCurrency['id']
                );
                if (isset($groupedPrices[$productId][$attributeWithCurrency['currency']])) {
                    $priceContainer[$priceColumn] = $groupedPrices[$productId][$attributeWithCurrency['currency']];
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
     * @param ProductUnit $unit
     */
    protected function addProductPriceCurrencyColumn(
        DatagridConfiguration $config,
        $currency,
        $priceAttributeId,
        ProductUnit $unit = null
    ) {
        $columnName = $this->buildColumnName($currency, $priceAttributeId, $unit);
        $joinAlias = $this->buildJoinAlias($columnName);

        $selectPattern = 'min(%s.value) as %s';
        $enabled = true;
        if ($unit) {
            $enabled = false;
            $selectPattern = '%s.value as %s';
        }

        $config->offsetAddToArrayByPath('[source][query][select]', [sprintf($selectPattern, $joinAlias, $columnName)]);

        $this->addConfigProductPriceJoin($config, $currency, $priceAttributeId, $unit);

        $column = $this->createPriceColumn($currency, $enabled, $unit);
        $config->offsetAddToArrayByPath('[columns]', [$columnName => $column]);
        $config->offsetAddToArrayByPath('[sorters][columns]', [$columnName => ['data_name' => $columnName]]);

        $this->addConfigFilter($config, $currency, $enabled, $unit);
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $currency
     * @param bool $enabled
     * @param ProductUnit|null $unit
     */
    protected function addConfigFilter(
        DatagridConfiguration $config,
        $currency,
        $enabled = true,
        $unit = null
    ) {
        $columnName = $this->buildColumnName($currency, $unit);
        $filter = ['type' => 'product-price', 'data_name' => $currency];
        if ($unit) {
            $filter = [
                'type' => 'number-range',
                'data_name' => $columnName,
                'enabled' => $enabled,
            ];
        }

        $config->offsetAddToArrayByPath('[filters][columns]', [$columnName => $filter]);
    }

    /**
     * {@inheritDoc}
     */
    protected function buildColumnName(
        $currencyIsoCode,
        $priceAttributeId,
        $unitCode = null
    ) {
        $result = 'price_column_'.strtolower($currencyIsoCode).'_'.$priceAttributeId;

        return $unitCode ? sprintf('%s_%s', $result, strtolower($unitCode)) : $result;
    }

    /**
     * @param string $currency
     * @param bool $renderable
     * @param ProductUnit|null $unit
     * @return array
     */
    protected function createPriceColumn(
        $currency,
        $renderable = true,
        ProductUnit $unit = null
    ) {
        $message = 'orob2b.pricing.productprice.price_in_%currency%';
        $params = ['%currency%' => $currency];
        if ($unit) {
            $message = 'orob2b.pricing.productprice.price_%unit%_in_%currency%';
            $params['%unit%'] = $unit->getCode();
        }

        return [
            'label' => $this->translator->trans($message, $params),
            'type' => 'twig',
            'template' => 'OroB2BPricingBundle:Datagrid:Column/productPrice.html.twig',
            'frontend_type' => 'html',
            'renderable' => $renderable,
        ];
    }

    /**
     * @return array|ProductUnit[]
     */
    protected function getAllUnits()
    {
        return $this->doctrineHelper
            ->getEntityRepository('OroB2BProductBundle:ProductUnit')
            ->findBy([], ['code' => 'ASC']);
    }

    /**
     * @param array|ProductPrice[] $productPrices
     * @return array
     */
    protected function getPricesByUnits(
        array $productPrices
    ) {
        $result = [];
        foreach ($productPrices as $productPrice) {
            if (null === $productPrice->getUnit()) {
                continue;
            }
            $currency = $productPrice->getPrice()->getCurrency();
            $unitCode = $productPrice->getUnit()->getCode();
            $result[$productPrice->getProduct()->getId()][$currency][$unitCode][] = $productPrice;
        }

        return $result;
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

        return $priceRepository->findByPriceAttributeProductPriceIdsIdsAndProductIds(
            $priceAttributeProductPriceIds,
            $productIds
        );
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $currency
     * @param integer $priceAttributeId
     * @param ProductUnit|null $unit
     */
    protected function addConfigProductPriceJoin(
        DatagridConfiguration $config,
        $currency,
        $priceAttributeId,
        $unit = null
    ) {
        $columnName = $this->buildColumnName($currency, $priceAttributeId, $unit);
        $joinAlias = $this->buildJoinAlias($columnName);
        $expr = new Expr();
        $joinExpr = $expr
            ->andX(sprintf('%s.product = product.id', $joinAlias))
            ->add($expr->eq(sprintf('%s.currency', $joinAlias), $expr->literal($currency)))
            ->add($expr->eq(sprintf('%s.priceList', $joinAlias), $expr->literal($priceAttributeId)))
            ->add($expr->eq(sprintf('%s.quantity', $joinAlias), 1));
        if ($unit) {
            $joinExpr->add($expr->eq(sprintf('%s.unit', $joinAlias), $expr->literal($unit)));
        }

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
    protected function buildJoinAlias(
        $columnName
    ) {
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
     * @param ProductPrice[] $prices
     * @return array
     */
    protected function groupPrices(
        array $prices
    ) {
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
}
