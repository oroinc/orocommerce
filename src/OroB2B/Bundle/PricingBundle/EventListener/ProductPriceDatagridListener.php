<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\ORM\Query\Expr;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class ProductPriceDatagridListener
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
        $currencies = $this->getCurrencies();
        if (!$currencies) {
            return;
        }

        $config = $event->getConfig();
        // add prices for currencies
        foreach ($currencies as $currencyIsoCode) {
            $this->addProductPriceCurrencyColumn($config, $currencyIsoCode);
        }

        $units = $this->getAllUnits();
        foreach ($currencies as $currencyIsoCode) {
            // add prices for units
            foreach ($units as $unit) {
                $this->addProductPriceCurrencyColumn($config, $currencyIsoCode, $unit);
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
        $units = $this->getAllUnits();

        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $showTierPrices = $this->priceListRequestHandler->getShowTierPrices();
        $prices = $this->getPrices($records, $showTierPrices);
        $pricesByUnits = $this->getPricesByUnits($prices);
        $groupedPrices = $this->groupPrices($prices);

        foreach ($records as $record) {
            $record->addData(['showTierPrices' => $showTierPrices]);

            $productId = $record->getValue('id');
            $priceContainer = [];
            foreach ($currencies as $currencyIsoCode) {
                foreach ($units as $unit) {
                    $priceUnitColumn = $this->buildColumnName($currencyIsoCode, $unit);

                    $data = [$priceUnitColumn => []];
                    if (isset($pricesByUnits[$productId][$currencyIsoCode][$unit->getCode()])) {
                        $data = [$priceUnitColumn => $pricesByUnits[$productId][$currencyIsoCode][$unit->getCode()]];
                    }

                    $record->addData($data);
                }

                $priceColumn = $this->buildColumnName($currencyIsoCode);
                if (isset($groupedPrices[$productId][$currencyIsoCode])) {
                    $priceContainer[$priceColumn] = $groupedPrices[$productId][$currencyIsoCode];
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
     * @param ProductUnit $unit
     * @param string $currency
     */
    protected function addProductPriceCurrencyColumn(DatagridConfiguration $config, $currency, ProductUnit $unit = null)
    {
        $columnName = $this->buildColumnName($currency, $unit);
        $joinAlias = $this->buildJoinAlias($columnName);

        $selectPattern = 'min(%s.value) as %s';
        $enabled = true;
        if ($unit) {
            $enabled = false;
            $selectPattern = '%s.value as %s';
        }

        $config->offsetAddToArrayByPath('[source][query][select]', [sprintf($selectPattern, $joinAlias, $columnName)]);

        $this->addConfigProductPriceJoin($config, $currency, $unit);

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
    protected function addConfigFilter(DatagridConfiguration $config, $currency, $enabled = true, $unit = null)
    {
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
    protected function buildColumnName($currencyIsoCode, $unitCode = null)
    {
        $result = 'price_column_' . strtolower($currencyIsoCode);
        return $unitCode ? sprintf('%s_%s', $result, strtolower($unitCode)) : $result;
    }

    /**
     * @return array
     */
    protected function getCurrencies()
    {
        return $this->priceListRequestHandler->getPriceListSelectedCurrencies($this->getPriceList());
    }

    /**
     * @param string $currency
     * @param bool $renderable
     * @param ProductUnit|null $unit
     * @return array
     */
    protected function createPriceColumn($currency, $renderable = true, ProductUnit $unit = null)
    {
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
    protected function getPricesByUnits(array $productPrices)
    {
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
     * @param bool $showTierPrices
     * @return ProductPrice[]
     */
    protected function getPrices(array $records, $showTierPrices)
    {
        /** @var ProductPriceRepository $priceRepository */
        $priceRepository = $this->doctrineHelper->getEntityRepository('OroB2BPricingBundle:ProductPrice');

        $productIds = array_map(function (ResultRecord $record) {
            return $record->getValue('id');
        }, $records);

        $priceList = $this->getPriceList();
        return $priceRepository->findByPriceListIdAndProductIds($priceList->getId(), $productIds, $showTierPrices);
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $currency
     * @param ProductUnit|null $unit
     */
    protected function addConfigProductPriceJoin(DatagridConfiguration $config, $currency, $unit = null)
    {
        $columnName = $this->buildColumnName($currency, $unit);
        $joinAlias = $this->buildJoinAlias($columnName);
        $priceList = $this->getPriceList();
        $expr = new Expr();
        $joinExpr = $expr
            ->andX(sprintf('%s.product = product.id', $joinAlias))
            ->add($expr->eq(sprintf('%s.currency', $joinAlias), $expr->literal($currency)))
            ->add($expr->eq(sprintf('%s.priceList', $joinAlias), $expr->literal($priceList->getId())))
            ->add($expr->eq(sprintf('%s.quantity', $joinAlias), 1));
        if ($unit) {
            $joinExpr->add($expr->eq(sprintf('%s.unit', $joinAlias), $expr->literal($unit)));
        }

        $config->offsetAddToArrayByPath(
            '[source][query][join][left]',
            [
                [
                    'join' => 'OroB2BPricingBundle:ProductPrice',
                    'alias' => $joinAlias,
                    'conditionType' => Expr\Join::WITH,
                    'condition' => (string)$joinExpr
                ]
            ]
        );
    }

    /**
     * @param string $columnName
     * @return string
     */
    protected function buildJoinAlias($columnName)
    {
        return $columnName . '_table';
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
}
