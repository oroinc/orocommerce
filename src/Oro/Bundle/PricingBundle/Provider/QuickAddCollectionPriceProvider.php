<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\QuickAddField;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;

/**
 * Adds prices to given QuickAddRowCollection according to given criteria
 */
class QuickAddCollectionPriceProvider
{
    private ProductPriceProviderInterface $productPriceProvider;

    private UserCurrencyManager $currencyManager;

    private DoctrineHelper $doctrineHelper;

    private RoundingServiceInterface $rounding;

    public function __construct(
        ProductPriceProviderInterface $productPriceProvider,
        UserCurrencyManager $currencyManager,
        DoctrineHelper $doctrineHelper,
        RoundingServiceInterface $rounding
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->currencyManager = $currencyManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->rounding = $rounding;
    }

    public function addAllPrices(
        QuickAddRowCollection $quickAddRowCollection,
        ProductPriceScopeCriteriaInterface $scopeCriteria
    ) {
        /** @var array{int: ProductPriceInterface[]} $productPricesByProductId */
        $productPricesByProductId = $this->productPriceProvider->getPricesByScopeCriteriaAndProducts(
            $scopeCriteria,
            $quickAddRowCollection->getProducts(),
            [$this->currencyManager->getUserCurrency()]
        );

        /** @var QuickAddRow $quickAddRow */
        foreach ($quickAddRowCollection as $quickAddRow) {
            if (!$quickAddRow->getProduct()) {
                continue;
            }

            $productId = $quickAddRow->getProduct()->getId();
            if (!isset($productPricesByProductId[$productId])) {
                continue;
            }

            /** @var ProductPriceInterface[] $productPrices */
            $productPrices = $productPricesByProductId[$productId];
            $rowPrices = [];
            foreach ($productPrices as $productPrice) {
                $priceValue = $productPrice->getPrice()->getValue();
                $priceCurrency = $productPrice->getPrice()->getCurrency();
                $unitCode = $productPrice->getUnit()->getCode();

                $rowPrices[$unitCode][] = [
                    'price' => $priceValue,
                    'currency' => $priceCurrency,
                    'quantity' => $productPrice->getQuantity(),
                    'unit' => $unitCode,
                ];
            }

            $quickAddRow->addAdditionalField(new QuickAddField('prices', $rowPrices));
        }
    }

    /**
     * @throws \Oro\Bundle\CurrencyBundle\Exception\InvalidRoundingTypeException
     */
    public function addPrices(
        QuickAddRowCollection $quickAddRowCollection,
        ProductPriceScopeCriteriaInterface $scopeCriteria
    ) {
        $rowsPriceCriteria = $this->buildQuickAddRowPriceCriteria($quickAddRowCollection);

        $productPrices = $this->getPricesForCriteria($rowsPriceCriteria, $scopeCriteria);

        $collectionSubtotal = [
            'value' => null,
            'currency' => $this->currencyManager->getUserCurrency()
        ];

        /** @var QuickAddRow $quickAddRow */
        foreach ($quickAddRowCollection->getValidRows() as $quickAddRow) {
            if (!isset($productPrices[$quickAddRow->getProduct()->getId()])) {
                continue;
            }

            $productPrice = $productPrices[$quickAddRow->getProduct()->getId()];
            $rowPrice = [
                'value' => $this->rounding->round($productPrice->getValue() * $quickAddRow->getQuantity()),
                'currency' => $productPrice->getCurrency()
            ];
            $quickAddRow->addAdditionalField(new QuickAddField('price', $rowPrice));
            $collectionSubtotal['value'] = $collectionSubtotal['value'] ?
                $collectionSubtotal['value'] + $rowPrice['value'] : $rowPrice['value'];
        }

        $quickAddRowCollection->addAdditionalField(new QuickAddField('price', $collectionSubtotal));
    }

    /**
     * @param ProductPriceCriteria[] $productPriceCriteria
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @return array
     */
    private function getPricesForCriteria(
        array $productPriceCriteria,
        ProductPriceScopeCriteriaInterface $scopeCriteria
    ) {
        $prices = $this->productPriceProvider->getMatchedPrices($productPriceCriteria, $scopeCriteria);
        $result = [];
        foreach ($prices as $key => $price) {
            $identifier = explode('-', $key);
            $result[$identifier[0]] = $price;
        }

        return $result;
    }

    /**
     * @param QuickAddRowCollection $quickAddRowCollection
     *
     * @return ProductPriceCriteria[]
     */
    private function buildQuickAddRowPriceCriteria(QuickAddRowCollection $quickAddRowCollection)
    {
        return array_map(function (QuickAddRow $quickAddRow) {
            return new ProductPriceCriteria(
                $quickAddRow->getProduct(),
                $this->doctrineHelper->getEntityReference(
                    ProductUnit::class,
                    $quickAddRow->getUnit()
                ),
                $quickAddRow->getQuantity(),
                $this->currencyManager->getUserCurrency()
            );
        }, $quickAddRowCollection->getValidRows()->toArray());
    }
}
