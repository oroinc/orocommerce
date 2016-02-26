<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Doctrine\ORM\Query\Expr;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

abstract class AbstractProductPriceDatagridListener
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
     * @var PriceListRequestHandler
     */
    protected $priceListRequestHandler;

    /**
     * @var string
     */
    protected $productPriceClass;

    /**
     * @var string
     */
    protected $productUnitClass;

    /**
     * @var BasePriceList
     */
    protected $priceList;

    /**
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     * @param PriceListRequestHandler $priceListRequestHandler
     */
    public function __construct(
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        PriceListRequestHandler $priceListRequestHandler
    ) {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->priceListRequestHandler = $priceListRequestHandler;
    }

    /**
     * @param string $productPriceClass
     */
    public function setProductPriceClass($productPriceClass)
    {
        $this->productPriceClass = $productPriceClass;
    }

    /**
     * @param string $productUnitClass
     */
    public function setProductUnitClass($productUnitClass)
    {
        $this->productUnitClass = $productUnitClass;
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

        // add prices for currencies
        foreach ($currencies as $currencyIsoCode) {
            $this->addProductPriceCurrencyColumn($config, $currencyIsoCode);
        }

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

        $productIds = [];
        foreach ($records as $record) {
            $productIds[] = $record->getValue('id');
        }

        /** @var ProductPriceRepository $priceRepository */
        $priceRepository = $this->doctrineHelper->getEntityRepository($this->productPriceClass);

        $priceList = $this->getPriceList();
        $showTierPrices = $this->priceListRequestHandler->getShowTierPrices();
        $prices = $priceRepository->findByPriceListIdAndProductIds($priceList->getId(), $productIds, $showTierPrices);
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
     * @param string $currencyIsoCode
     * @param string $unitCode
     * @return string
     */
    abstract protected function buildColumnName($currencyIsoCode, $unitCode = null);

    /**
     * @param string $columnName
     * @return string
     */
    protected function buildJoinAlias($columnName)
    {
        return $columnName . '_table';
    }

    /**
     * @return BasePriceList
     */
    protected function getPriceList()
    {
        if (!$this->priceList) {
            $this->priceList = $this->providePriceList();
        }
        return $this->priceList;
    }

    /**
     * @return BasePriceList
     */
    abstract protected function providePriceList();

    /**
     * @return array
     */
    protected function getCurrencies()
    {
        return $this->priceListRequestHandler->getPriceListSelectedCurrencies($this->getPriceList());
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
     * @param string $currency
     */
    protected function addProductPriceCurrencyColumn(DatagridConfiguration $config, $currency, ProductUnit $unit = null)
    {
        $columnName = $this->buildColumnName($currency, $unit);
        $joinAlias = $this->buildJoinAlias($columnName);
        $priceList = $this->getPriceList();

        $selectPattern = 'min(%s.value) as %s';
        $enabled = true;
        if ($unit) {
            $enabled = false;
            $selectPattern = '%s.value as %s';
        }

        $this->addConfigElement($config, '[source][query][select]', sprintf($selectPattern, $joinAlias, $columnName));

        $expr = new Expr();
        $joinExpr = $expr
            ->andX(sprintf('%s.product = product.id', $joinAlias))
            ->add($expr->eq(sprintf('%s.currency', $joinAlias), $expr->literal($currency)))
            ->add($expr->eq(sprintf('%s.priceList', $joinAlias), $expr->literal($priceList->getId())))
            ->add($expr->eq(sprintf('%s.quantity', $joinAlias), 1));
        if ($unit) {
            $joinExpr->add($expr->eq(sprintf('%s.unit', $joinAlias), $expr->literal($unit)));
        }
        $this->addConfigElement($config, '[source][query][join][left]', [
            'join' => $this->productPriceClass,
            'alias' => $joinAlias,
            'conditionType' => Expr\Join::WITH,
            'condition' => (string)$joinExpr,
        ]);

        $column = $this->createPriceColumn($currency, $enabled, $unit);
        $this->addConfigElement($config, '[columns]', $column, $columnName);

        $this->addConfigElement($config, '[sorters][columns]', $this->getSorter($columnName), $columnName);

        $filter = ['type' => 'product-price', 'data_name' => $currency];
        if ($unit) {
            $filter = [
                'type' => 'number-range',
                'data_name' => $columnName,
                'enabled' => $enabled,
            ];
        }
        $this->addConfigElement($config, '[filters][columns]', $filter, $columnName);
    }

    /**
     * @param string $currency
     * @param bool $renderable
     * @param ProductUnit $unit
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
            'template' => $this->getColumnTemplate(),
            'frontend_type' => 'html',
            'renderable' => $renderable,
        ];
    }

    /**
     * @return string
     */
    abstract protected function getColumnTemplate();

    /**
     * @param $columnName
     * @return array
     */
    protected function getSorter($columnName)
    {
        return [
            'data_name' => $columnName,
            'type' => PropertyInterface::TYPE_CURRENCY,
        ];
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
     * @return array|ProductUnit[]
     */
    protected function getAllUnits()
    {
        return $this->doctrineHelper->getEntityRepository($this->productUnitClass)->findBy([], ['code' => 'ASC']);
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
}
