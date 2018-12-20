<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Exception\UnexpectedTypeException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Provider\PriceAttributePricesProvider;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Adds price attribute columns to price attributes datagrids on the product view page.
 */
class ProductPriceAttributesGridListener
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var PriceAttributePricesProvider
     */
    protected $priceAttributePricesProvider;

    /**
     * @param DoctrineHelper $helper
     * @param PriceAttributePricesProvider $provider
     */
    public function __construct(
        DoctrineHelper $helper,
        PriceAttributePricesProvider $provider
    ) {
        $this->doctrineHelper = $helper;
        $this->priceAttributePricesProvider = $provider;
    }

    /**
     * @param BuildBefore $event
     * @throws \LogicException
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $priceListId = $this->getParameter($event->getDatagrid(), 'price_list_id');

        /** @var PriceAttributePriceList|null $priceList */
        $priceList = $this->doctrineHelper->getEntity(PriceAttributePriceList::class, $priceListId);
        if (!$priceList) {
            throw new \LogicException(sprintf('Can not find price attribute price list (id: %d)', $priceListId));
        }

        $config = $event->getConfig();
        foreach ($priceList->getCurrencies() as $currency) {
            $columnPath = sprintf(DatagridConfiguration::COLUMN_PATH, $currency);
            $columnConfig['label'] = $currency;
            $columnConfig = [
                    'label' => $currency,
                    'type' => 'twig',
                    'template' => 'OroPricingBundle:Datagrid:Column/priceValue.html.twig',
                    'frontend_type' => 'html',
                ] + $config->offsetGetByPath($columnPath, []);
            $config->offsetSetByPath($columnPath, $columnConfig);

            $sortersPath = sprintf(DatagridConfiguration::SORTER_PATH, $currency);
            $sorterConfig = $config->offsetGetByPath($sortersPath, []);
            $sorterConfig['data_name'] = $currency;
            $config->offsetSetByPath($sortersPath, $sorterConfig);
        }
    }

    /**
     * @param BuildAfter $event
     * @throws \LogicException
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();

        if (!$datasource instanceof ArrayDatasource) {
            throw new UnexpectedTypeException($datasource, ArrayDatasource::class);
        }

        $productId = $this->getParameter($datagrid, 'product_id');
        $priceListId = $this->getParameter($datagrid, 'price_list_id');

        /** @var Product|null $product */
        $product = $this->doctrineHelper->getEntity(Product::class, $productId);

        if (!$product) {
            throw new \LogicException(sprintf('Can not find product (id: %d)', $productId));
        }

        /** @var PriceAttributePriceList|null $priceList */
        $priceList = $this->doctrineHelper->getEntity(PriceAttributePriceList::class, $priceListId);
        if (!$priceList) {
            throw new \LogicException(sprintf('Can not find price attribute price list (id: %d)', $priceListId));
        }

        $datasource->setArraySource($this->createSource($priceList, $product));
    }

    /**
     * @param PriceAttributePriceList $priceList
     * @param Product $product
     * @return array
     */
    protected function createSource(PriceAttributePriceList $priceList, Product $product)
    {
        $source = [];
        $currencies = $priceList->getCurrencies();

        $priceAttributePrices = $this->priceAttributePricesProvider
            ->getPricesWithUnitAndCurrencies($priceList, $product);

        /** @var PriceAttributeProductPrice[] $prices */
        foreach ($priceAttributePrices as $unitCode => $prices) {
            $row = ['unit' => $unitCode];
            foreach ($currencies as $currency) {
                $result = !empty($prices[$currency]) ?
                    $prices[$currency]->getPrice()->getValue() :
                    null;

                $row[$currency] = $result;
            }
            $source[] = $row;
        }

        return $source;
    }

    /**
     * @param DatagridInterface $datagrid
     * @param string $parameterName
     * @return mixed
     */
    protected function getParameter(DatagridInterface $datagrid, $parameterName)
    {
        $value = $datagrid->getParameters()->get($parameterName);

        if ($value === null) {
            throw new \LogicException(sprintf('Parameter "%s" must be set', $parameterName));
        }

        return $value;
    }
}
