<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Provider\PriceAttributePricesProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

use Symfony\Component\Translation\TranslatorInterface;

class ProductPriceAttributesGridListener
{
    /**
     * @var DoctrineHelper
     */
    protected $helper;

    /**
     * @var PriceAttributePricesProvider
     */
    protected $provider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * ProductPriceAttributesGridListener constructor.
     * @param DoctrineHelper $helper
     * @param PriceAttributePricesProvider $provider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        DoctrineHelper $helper,
        PriceAttributePricesProvider $provider,
        TranslatorInterface $translator
    ) {
        $this->helper = $helper;
        $this->provider = $provider;
        $this->translator = $translator;
    }

    /**
     * @param BuildBefore $event
     * @throws \LogicException
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $priceListId = $event->getDatagrid()->getParameters()->get('price_list_id');
        if (!$priceListId) {
            throw new \LogicException('Cant find price list id among datagrid`s parameters');
        }
        /** @var PriceAttributePriceList|null $priceList */
        $priceList = $this->helper->getEntity(PriceAttributePriceList::class, $priceListId);
        if (!$priceList) {
            throw new \LogicException("Cant find price list with id '$priceListId'");
        }

        $config = $event->getConfig();
        foreach ($priceList->getCurrencies() as $currency) {
            $columnPath = sprintf(DatagridConfiguration::COLUMN_PATH, $currency);
            $columnConfig = $config->offsetGetByPath($columnPath, []);
            $columnConfig['label'] = $currency;
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
        $datasource = $event->getDatagrid()->getDatasource();
        if (!$datasource instanceof ArrayDatasource) {
            throw new \LogicException('Wrong datasource type');
        }
        $productId = $event->getDatagrid()->getParameters()->get('product_id');
        $priceListId = $event->getDatagrid()->getParameters()->get('price_list_id');
        if (!$productId || !$priceListId) {
            throw new \LogicException('Cant find required price list & product ids among datagrid`s parameters');
        }

        /** @var Product|null $product */
        $product = $this->helper->getEntity(Product::class, $productId);
        /** @var PriceAttributePriceList|null $priceList */
        $priceList = $this->helper->getEntity(PriceAttributePriceList::class, $priceListId);
        if (!$product || !$priceList) {
            throw new \LogicException('Cant find price list or product with specified ids');
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

        /** @var ProductUnit $unit */
        /** @var PriceAttributeProductPrice[] $prices */
        foreach ($this->provider->getPrices($priceList, $product) as $unitCode => $prices) {
            $row = ['unit' => $this->capitalize($unitCode)];
            foreach ($currencies as $currency) {
                $result = !empty($prices[$currency]) ?
                    $prices[$currency]->getPrice()->getValue() :
                    $this->translator->trans('oro.pricing.priceAttribute.withoutPrice');

                $row[$currency] = $result;
            }
            $source[] = $row;
        }
        return $source;
    }

    /**
     * @desc own variant of ucfirst() because last one does not work with UTF-8
     * @param string $string
     * @return string
     */
    protected function capitalize($string)
    {
        return mb_strtoupper(mb_substr($string, 0, 1)) . mb_substr($string, 1);
    }
}
