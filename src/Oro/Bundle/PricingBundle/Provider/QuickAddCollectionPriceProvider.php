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
 * Adds prices to QuickAddRowCollection according to a specific criteria.
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
    ): void {
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

    public function addPrices(
        QuickAddRowCollection $quickAddRowCollection,
        ProductPriceScopeCriteriaInterface $scopeCriteria
    ): void {
        $validRows = $quickAddRowCollection->getValidRows();
        $rowsPriceCriteria = $this->buildQuickAddRowPriceCriteria($validRows);

        $productPrices = $this->getPricesForCriteria($rowsPriceCriteria, $scopeCriteria);

        $collectionSubtotal = [
            'value' => null,
            'currency' => $this->currencyManager->getUserCurrency()
        ];

        /** @var QuickAddRow $quickAddRow */
        foreach ($validRows as $quickAddRow) {
            $priceIndex = $quickAddRow->getProduct()->getId() . '-' . $quickAddRow->getUnit();
            if (!isset($productPrices[$priceIndex])) {
                continue;
            }

            $productPrice = $productPrices[$priceIndex];
            $rowUnitPrice = [
                'value' => $productPrice->getValue(),
                'currency' => $productPrice->getCurrency()
            ];
            $quickAddRow->addAdditionalField(new QuickAddField('unitPrice', $rowUnitPrice));
            $rowPrice = [
                'value' => $this->rounding->round($productPrice->getValue() * $quickAddRow->getQuantity()),
                'currency' => $productPrice->getCurrency()
            ];
            $quickAddRow->addAdditionalField(new QuickAddField('price', $rowPrice));
            $collectionSubtotal['value'] = $collectionSubtotal['value']
                ? $collectionSubtotal['value'] + $rowPrice['value']
                : $rowPrice['value'];
        }

        $quickAddRowCollection->addAdditionalField(new QuickAddField('price', $collectionSubtotal));
    }

    private function getPricesForCriteria(
        array $productPriceCriteria,
        ProductPriceScopeCriteriaInterface $scopeCriteria
    ): array {
        $prices = $this->productPriceProvider->getMatchedPrices($productPriceCriteria, $scopeCriteria);
        $result = [];
        foreach ($prices as $key => $price) {
            [$productId, $unitName] = explode('-', $key);
            $result[$productId . '-' . $unitName] = $price;
        }

        return $result;
    }

    private function buildQuickAddRowPriceCriteria(QuickAddRowCollection $validRows): array
    {
        $result = [];
        /** @var QuickAddRow $row */
        foreach ($validRows as $row) {
            $result[] = new ProductPriceCriteria(
                $row->getProduct(),
                $this->doctrineHelper->getEntityReference(ProductUnit::class, $row->getUnit()),
                $row->getQuantity(),
                $this->currencyManager->getUserCurrency()
            );
        }

        return $result;
    }
}
