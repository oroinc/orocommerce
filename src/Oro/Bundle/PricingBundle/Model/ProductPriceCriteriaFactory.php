<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\PricingBundle\Exception\ProductPriceCriteriaBuildingFailedException;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Psr\Log\LoggerInterface;

/**
 * The factory to create the ProductPriceCriteria.
 */
class ProductPriceCriteriaFactory implements ProductPriceCriteriaFactoryInterface
{
    private UserCurrencyManager $currencyManager;
    private LoggerInterface $logger;

    public function __construct(UserCurrencyManager $currencyManager, LoggerInterface $logger)
    {
        $this->currencyManager = $currencyManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function build(
        Product $product,
        ProductUnit $productUnit,
        float $quantity,
        ?string $currency = null
    ): ProductPriceCriteria {
        if (null === $currency) {
            $currency = $this->currencyManager->getUserCurrency();
        }

        try {
            return new ProductPriceCriteria($product, $productUnit, $quantity, $currency);
        } catch (\InvalidArgumentException $e) {
            throw $this->handleError($e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createListFromProductLineItems(iterable $productLineItems, ?string $currency = null): array
    {
        $results = [];

        if (null === $currency) {
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

    /**
     * {@inheritDoc}
     */
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

    private function handleError(string $exceptionMessage): ProductPriceCriteriaBuildingFailedException
    {
        $exception = new ProductPriceCriteriaBuildingFailedException();

        $this->logger->error(
            'Got error while trying to create new ProductPriceCriteria with message: "{message}"',
            [
                'message' => $exceptionMessage,
                'exception' => $exception
            ]
        );

        return $exception;
    }

    private function validateProductLineItem(ProductLineItemInterface $productLineItem): void
    {
        if (null === $productLineItem->getProduct()) {
            throw $this->handleError('The product property of ProductLineItem should not be null');
        }
        if (null === $productLineItem->getProductUnit()) {
            throw $this->handleError('The product unit property of ProductLineItem should not be null');
        }
        if (null === $productLineItem->getQuantity()) {
            throw $this->handleError('The quantity property of ProductLineItem should not be null');
        }
    }
}
