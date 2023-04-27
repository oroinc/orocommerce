<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\PricingBundle\Exception\ProductPriceCriteriaBuildingFailedException;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Psr\Log\LoggerInterface;

/**
 * {@inheritdoc}
 */
class ProductPriceCriteriaFactory implements ProductPriceCriteriaFactoryInterface
{
    private LoggerInterface $logger;

    private UserCurrencyManager $currencyManager;

    public function __construct(LoggerInterface $logger, UserCurrencyManager $currencyManager)
    {
        $this->logger = $logger;
        $this->currencyManager = $currencyManager;
    }

    public function build(
        Product $product,
        ProductUnit $productUnit,
        float $quantity,
        ?string $currency = null
    ): ProductPriceCriteria {
        if (is_null($currency)) {
            $currency = $this->currencyManager->getUserCurrency();
        }

        try {
            return new ProductPriceCriteria($product, $productUnit, $quantity, $currency);
        } catch (\InvalidArgumentException $e) {
            $this->handleError($e->getMessage());
        }
    }

    /**
     * @param iterable<ProductLineItemInterface> $productLineItems
     * @param string|null $currency
     * @return ProductPriceCriteria[]
     */
    public function createListFromProductLineItems(iterable $productLineItems, ?string $currency = null): array
    {
        $results = [];

        if (is_null($currency)) {
            $currency = $this->currencyManager->getUserCurrency();
        }

        foreach ($productLineItems as $idx => $productLineItem) {
            try {
                $results[$idx] = $this->createFromProductLineItem($productLineItem, $currency);
            } catch (ProductPriceCriteriaBuildingFailedException) {
                continue;
            }
        }

        return $results;
    }

    public function createFromProductLineItem(
        ProductLineItemInterface $productLineItem,
        ?string $currency = null
    ): ProductPriceCriteria {
        $this->validateProductLineItem($productLineItem);

        return $this->build(
            $productLineItem->getProduct(),
            $productLineItem->getProductUnit(),
            (float)$productLineItem->getQuantity(),
            $currency
        );
    }

    private function handleError(string $exceptionMessage): void
    {
        $exception = new ProductPriceCriteriaBuildingFailedException();

        $this->logger->error(
            'Got error while trying to create new ProductPriceCriteria with message: "{message}"',
            [
                'message' => $exceptionMessage,
                'exception' => $exception
            ]
        );

        throw $exception;
    }

    private function validateProductLineItem(ProductLineItemInterface $productLineItem)
    {
        if (is_null($productLineItem->getProduct())) {
            $this->handleError('The product property of ProductLineItem should not be null');
        }

        if (is_null($productLineItem->getProductUnit())) {
            $this->handleError('The product unit property of ProductLineItem should not be null');
        }

        if (is_null($productLineItem->getQuantity())) {
            $this->handleError('The quantity property of ProductLineItem should not be null');
        }
    }
}
