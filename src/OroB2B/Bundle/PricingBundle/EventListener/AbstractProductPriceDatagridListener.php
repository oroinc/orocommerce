<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\ORM\Query\Expr;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

abstract class AbstractProductPriceDatagridListener
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
     * @var string
     */
    protected $productPriceClass;

    /**
     * @var BasePriceList
     */
    protected $priceList;

    /**
     * @param TranslatorInterface $translator
     * @param PriceListRequestHandler $priceListRequestHandler
     */
    public function __construct(TranslatorInterface $translator, PriceListRequestHandler $priceListRequestHandler)
    {
        $this->translator = $translator;
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
     * @param BuildBefore $event
     */
    abstract public function onBuildBefore(BuildBefore $event);

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
    abstract protected function getCurrencies();

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
}
