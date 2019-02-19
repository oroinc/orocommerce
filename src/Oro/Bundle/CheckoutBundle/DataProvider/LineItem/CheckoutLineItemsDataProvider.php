<?php

namespace Oro\Bundle\CheckoutBundle\DataProvider\LineItem;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Component\Checkout\DataProvider\AbstractCheckoutProvider;

/**
 * Provide info to build collection of line items by given source entity.
 * Source entity should implement ProductLineItemsHolderInterface.
 */
class CheckoutLineItemsDataProvider extends AbstractCheckoutProvider
{
    /**
     * @var FrontendProductPricesDataProvider
     */
    protected $frontendProductPricesDataProvider;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param FrontendProductPricesDataProvider $frontendProductPricesDataProvider
     * @param ManagerRegistry $registry
     */
    public function __construct(
        FrontendProductPricesDataProvider $frontendProductPricesDataProvider,
        ManagerRegistry $registry
    ) {
        $this->frontendProductPricesDataProvider = $frontendProductPricesDataProvider;
        $this->registry = $registry;
    }

    /**
     * @param Checkout $entity
     *
     * {@inheritDoc}
     */
    protected function prepareData($entity)
    {
        $lineItems = $entity->getLineItems();
        $lineItemsPrices = $this->findPrices($lineItems);

        $data = [];

        /** @var CheckoutLineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            $unitCode = $lineItem->getProductUnitCode();
            $product = $lineItem->getProduct();

            $price = $lineItem->getPrice();
            if (!$price &&
                $product &&
                !$lineItem->isPriceFixed() &&
                isset($lineItemsPrices[$product->getId()][$unitCode])
            ) {
                $price = $lineItemsPrices[$product->getId()][$unitCode];
            }

            $data[] = [
                'productSku' => $lineItem->getProductSku(),
                'comment' => $lineItem->getComment(),
                'quantity' => $lineItem->getQuantity(),
                'productUnit' => $lineItem->getProductUnit(),
                'productUnitCode' => $unitCode,
                'product' => $product,
                'parentProduct' => $lineItem->getParentProduct(),
                'freeFormProduct' => $lineItem->getFreeFormProduct(),
                'fromExternalSource' => $lineItem->isFromExternalSource(),
                'price' => $price,
            ];
        }

        return $data;
    }

    /**
     * @param Collection $lineItems
     *
     * @return array
     */
    protected function findPrices(Collection $lineItems)
    {
        $lineItemsWithoutPrice = $lineItems->filter(
            function (CheckoutLineItem $lineItem) {
                return !$lineItem->isPriceFixed() && !$lineItem->getPrice() && $lineItem->getProduct();
            }
        )->toArray();

        if (!$lineItemsWithoutPrice) {
            return [];
        }

        return $this->frontendProductPricesDataProvider->getProductsMatchedPrice($lineItemsWithoutPrice);
    }

    /**
     * {@inheritDoc}
     */
    public function isEntitySupported($transformData)
    {
        return $transformData instanceof Checkout;
    }
}
