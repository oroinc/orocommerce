<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Allows to get formatted matching prices for products from given line items with given criteria
 * Allows to get supported currencies according to given criteria
 */
class MatchingPriceProvider
{
    /** @var ProductPriceProviderInterface */
    protected $productPriceProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var string */
    protected $productClass;

    /** @var string */
    protected $productUnitClass;

    private ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory;

    public function __construct(
        ProductPriceProviderInterface $productPriceProvider,
        DoctrineHelper $doctrineHelper,
        ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory,
        string $productClass,
        string $productUnitClass
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->productClass = $productClass;
        $this->productUnitClass = $productUnitClass;
        $this->productPriceCriteriaFactory = $productPriceCriteriaFactory;
    }

    /**
     * @param array $lineItems
     * @param ProductPriceScopeCriteriaInterface $priceScopeCriteria
     * @return array
     */
    public function getMatchingPrices(array $lineItems, ProductPriceScopeCriteriaInterface $priceScopeCriteria)
    {
        $productsPriceCriteria = $this->prepareProductsPriceCriteria($lineItems);

        $matchedPrice = $this->productPriceProvider->getMatchedPrices($productsPriceCriteria, $priceScopeCriteria);

        return $this->formatMatchedPrices($matchedPrice);
    }

    /**
     * @param ProductPriceScopeCriteriaInterface $priceScopeCriteria
     * @return array|string[]
     */
    public function getSupportedCurrencies(ProductPriceScopeCriteriaInterface $priceScopeCriteria)
    {
        return $this->productPriceProvider->getSupportedCurrencies($priceScopeCriteria);
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

                $productPriceCriterion = $this->productPriceCriteriaFactory
                    ->create($product, $unit, $quantity, $currency);
                if ($productPriceCriterion !== null) {
                    $productsPriceCriteria[] = $productPriceCriterion;
                }
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
