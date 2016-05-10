<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Model\ProductPriceCriteria;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class MatchingPriceProvider
{
    /** @var ProductPriceProvider */
    protected $productPriceProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var string */
    protected $productClass;

    /** @var string */
    protected $productUnitClass;

    /**
     * @param ProductPriceProvider $productPriceProvider
     * @param DoctrineHelper $doctrineHelper
     * @param string $productClass
     * @param string $productUnitClass
     */
    public function __construct(
        ProductPriceProvider $productPriceProvider,
        DoctrineHelper $doctrineHelper,
        $productClass,
        $productUnitClass
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->productClass = (string)$productClass;
        $this->productUnitClass = (string)$productUnitClass;
    }

    /**
     * @param array $lineItems
     * @param BasePriceList $priceList
     * @return array
     */
    public function getMatchingPrices(array $lineItems, BasePriceList $priceList)
    {
        $productsPriceCriteria = $this->prepareProductsPriceCriteria($lineItems);

        $matchedPrice = $this->productPriceProvider->getMatchedPrices($productsPriceCriteria, $priceList);

        return $this->formatMatchedPrices($matchedPrice);
    }

    /**
     * @param Price[] $matchedPrice
     * @return array
     */
    protected function formatMatchedPrices(array $matchedPrice)
    {
        $result = [];
        foreach ($matchedPrice as $key => $price) {
            if ($price) {
                $result[$key]['value'] = $price->getValue();
                $result[$key]['currency'] = $price->getCurrency();
            }
        }

        return $result;
    }

    /**
     * @param array $lineItems
     * @return array
     */
    protected function prepareProductsPriceCriteria(array $lineItems)
    {
        $productsPriceCriteria = [];

        foreach ($lineItems as $lineItem) {
            $productId = $this->getLineItemData($lineItem, 'product');
            $productUnitCode = $this->getLineItemData($lineItem, 'unit');

            if ($productId && $productUnitCode) {
                /** @var Product $product */
                $product = $this->doctrineHelper->getEntityReference(
                    $this->productClass,
                    $productId
                );

                /** @var ProductUnit $unit */
                $unit = $this->doctrineHelper->getEntityReference(
                    $this->productUnitClass,
                    $productUnitCode
                );

                $quantity = (float)$this->getLineItemData($lineItem, 'qty');
                $currency = (string)$this->getLineItemData($lineItem, 'currency');

                $productsPriceCriteria[] = new ProductPriceCriteria($product, $unit, $quantity, $currency);
            }
        }

        return $productsPriceCriteria;
    }

    /**
     * @param array $lineItem
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getLineItemData(array $lineItem, $key, $default = null)
    {
        $data = $default;
        if (array_key_exists($key, $lineItem)) {
            $data = $lineItem[$key];
        }

        return $data;
    }
}
