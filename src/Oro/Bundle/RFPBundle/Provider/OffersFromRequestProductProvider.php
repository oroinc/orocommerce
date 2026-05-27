<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Provider;

use Oro\Bundle\RFPBundle\Entity\RequestProduct;

/**
 * Extracts offer data from a {@see RequestProduct}, filtered by available product units and optionally by currency.
 */
class OffersFromRequestProductProvider
{
    /**
     * Returns the list of offers for the given RequestProduct.
     * When $currency is provided, only offers matching that currency are returned.
     *
     * @return list<array{unit: string, quantity: float|int, price?: float|int|string, currency?: string}>
     */
    public function getOffers(RequestProduct $requestProduct, ?string $currency = null): array
    {
        $offers = [];
        $product = $requestProduct->getProduct();
        foreach ($requestProduct->getRequestProductItems() as $requestProductItem) {
            if (
                $product !== null
                && !isset($product->getAvailableUnits()[$requestProductItem->getProductUnitCode()])
            ) {
                // Skips the offer if the unit is not available for the product.
                continue;
            }

            $offer = [
                'unit' => $requestProductItem->getProductUnitCode(),
                'quantity' => $requestProductItem->getQuantity(),
            ];

            $price = $requestProductItem->getPrice();
            if ($price !== null) {
                $offer['price'] = $price->getValue();
                $offer['currency'] = $price->getCurrency();

                if ($currency !== null && $price->getCurrency() !== null && $price->getCurrency() !== $currency) {
                    // Skips the offer if it does not match the requested currency.
                    continue;
                }
            }

            $offers[] = $offer;
        }

        return $offers;
    }
}
