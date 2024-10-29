<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductKitPrice;

use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks that all product kit required filters are provided.
 */
class HandleProductKitPriceFilters implements ProcessorInterface
{
    private const string KIT_ITEM_PRODUCT_FILTER_KEY = 'kitItems.%s.product';
    private const string KIT_ITEM_QUANTITY_FILTER_KEY = 'kitItems.%s.quantity';

    public function __construct(
        private DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        $this->checkOnRequiredFilters($context);

        $product = $this->getProduct($context->getFilterValues()->getOne('product')?->getValue());
        if ($product === null) {
            return;
        }

        $this->checkOnProductKitRequiredFilters($context, $product);
        $this->checkOnBelongingKitItemToKitProduct($context, $product);
    }

    private function checkOnRequiredFilters(Context $context): void
    {
        $filterValues = $context->getFilterValues();
        if (!$filterValues->getOne('customer')) {
            $context->addError(Error::createValidationError(Constraint::FILTER, 'The "customer" filter is required.'));
        }
        if (!$filterValues->getOne('website')) {
            $context->addError(Error::createValidationError(Constraint::FILTER, 'The "website" filter is required.'));
        }
        if (!$filterValues->getOne('unit')) {
            $context->addError(Error::createValidationError(Constraint::FILTER, 'The "unit" filter is required.'));
        }
        if (!$filterValues->getOne('product')) {
            $context->addError(Error::createValidationError(Constraint::FILTER, 'The "product" filter is required.'));
        }
        if (!$filterValues->getOne('quantity')) {
            $context->addError(Error::createValidationError(Constraint::FILTER, 'The "quantity" filter is required.'));
        }
    }

    private function checkOnProductKitRequiredFilters(Context $context, Product $product): void
    {
        if ($product->isKit() === false) {
            $context->addError(
                Error::createValidationError(Constraint::VALUE, 'The resource supports only "kit" products.')
            );
        }

        foreach ($product->getKitItems() as $kitItem) {
            $this->checkOnProductKitItemRequiredFilters($context, $kitItem);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function checkOnProductKitItemRequiredFilters(Context $context, ProductKitItem $kitItem): void
    {
        $filterValues = $context->getFilterValues();
        $isRequired = $this->isRequiredKitItem($kitItem);

        $kitItemProdFilter = $filterValues->getOne(\sprintf(self::KIT_ITEM_PRODUCT_FILTER_KEY, $kitItem->getId()));
        if ($isRequired && !$kitItemProdFilter) {
            $context->addError(
                Error::createValidationError(
                    Constraint::FILTER,
                    \sprintf('The "filter[kitItems][%s][product]" filter is required.', $kitItem->getId())
                )
            );
        }

        if ($kitItemProdFilter && !$this->isProductBelongsToKitItem($kitItem, $kitItemProdFilter->getValue())) {
            $context->addError(
                Error::createValidationError(Constraint::VALUE, \sprintf(
                    'The kit item product #%s does not belong to kit item #%s.',
                    $kitItemProdFilter->getValue(),
                    $kitItem->getId()
                ))
            );
        }

        $kitItemQtyFilter = $filterValues->getOne(\sprintf(self::KIT_ITEM_QUANTITY_FILTER_KEY, $kitItem->getId()));
        if ($isRequired && !$kitItemQtyFilter) {
            $context->addError(
                Error::createValidationError(
                    Constraint::FILTER,
                    \sprintf('The "filter[kitItems][%s][quantity]" filter is required.', $kitItem->getId())
                )
            );
        }

        if ($kitItemQtyFilter && !$this->isValidKitItemQuantity($kitItem, $kitItemQtyFilter->getValue())) {
            $context->addError(
                Error::createValidationError(Constraint::VALUE, $this->getKitItemQuantityMessage($kitItem))
            );
        }

        // Check missed filters for optional kit items
        if (!$isRequired && $this->isSkippedOptionalKitItemFilter($kitItemProdFilter, $kitItemQtyFilter)) {
            $context->addError(
                Error::createValidationError(Constraint::FILTER, \sprintf(
                    'The "filter[kitItems][%s][%s]" filter is missed.',
                    $kitItem->getId(),
                    !$kitItemProdFilter ? 'product' : 'quantity',
                ))
            );
        }
    }

    private function checkOnBelongingKitItemToKitProduct(Context $context, Product $product): void
    {
        $filters = $context->getFilterValues()->getAll();
        $kitItemIds = $product->getKitItems()->map(static fn (ProductKitItem $kitItem) => $kitItem->getId())->toArray();

        foreach ($filters as $filterKey => $filter) {
            if (!AddKitItemFilters::isKitItemFilter($filterKey)) {
                continue;
            }

            [, $kitItemId,] = \explode('.', $filterKey);
            if (!\in_array((int)$kitItemId, $kitItemIds, true)) {
                $context->addError(
                    Error::createValidationError(
                        Constraint::VALUE,
                        \sprintf('The kit item #%s does not belong to product kit #%s.', $kitItemId, $product->getId())
                    )
                );
            }
        }
    }

    private function getProduct(?int $productId): ?Product
    {
        return $productId ? $this->doctrineHelper->getEntity(Product::class, $productId) : null;
    }

    private function isRequiredKitItem(ProductKitItem $kitItem): bool
    {
        return $kitItem->isOptional() === false;
    }

    private function isProductBelongsToKitItem(ProductKitItem $kitItem, int $productId): bool
    {
        return !$kitItem->getProducts()
            ->filter(static fn (Product $product) => $product->getId() === $productId)
            ->isEmpty();
    }

    private function isSkippedOptionalKitItemFilter(?FilterValue $prodFilter, ?FilterValue $qtyFilter): bool
    {
        return \count(\array_filter([$prodFilter, $qtyFilter])) === 1;
    }

    private function isValidKitItemQuantity(ProductKitItem $kitItem, int $quantity): bool
    {
        $isValid = true;
        if ($kitItem->getMinimumQuantity() && $kitItem->getMinimumQuantity() > $quantity) {
            $isValid = false;
        }

        if ($kitItem->getMaximumQuantity() && $kitItem->getMaximumQuantity() < $quantity) {
            $isValid = false;
        }

        return $isValid;
    }

    private function getKitItemQuantityMessage(ProductKitItem $kitItem): string
    {
        $message = \sprintf('The "filter[kitItems][%s][quantity]" filter value should be', $kitItem->getId());
        if ($kitItem->getMinimumQuantity() && $kitItem->getMaximumQuantity()) {
            $message .= \sprintf(' between %s and %s.', $kitItem->getMinimumQuantity(), $kitItem->getMaximumQuantity());
            return $message;
        }

        if ($kitItem->getMinimumQuantity()) {
            $message .= \sprintf(' equals to or exceed %s.', $kitItem->getMinimumQuantity());
        }

        if ($kitItem->getMaximumQuantity()) {
            $message .= \sprintf(' equals to or less than %s.', $kitItem->getMaximumQuantity());
        }

        return $message;
    }
}
