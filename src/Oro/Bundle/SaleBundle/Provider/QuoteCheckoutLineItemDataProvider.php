<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Component\Checkout\DataProvider\AbstractCheckoutProvider;

class QuoteCheckoutLineItemDataProvider extends AbstractCheckoutProvider
{
    /**
     * {@inheritDoc}
     */
    public function isEntitySupported($entity)
    {
        return $entity instanceof QuoteDemand;
    }

    /**
     * @param QuoteDemand $entity
     * {@inheritdoc}
     */
    protected function prepareData($entity)
    {
        $result = [];
        foreach ($entity->getDemandProducts() as $demandProduct) {
            $productOffer = $demandProduct->getQuoteProductOffer();
            if ($productOffer) {
                $quoteProduct = $productOffer->getQuoteProduct();
                $productSku = $productOffer->getProductSku() ?: $quoteProduct->getProductSku();
                $result[] = [
                    'product' => $productOffer->getProduct(),
                    'freeFormProduct' => $productOffer->getProduct() ? null : $quoteProduct->getFreeFormProduct(),
                    'productSku' => $productSku,
                    'quantity' => $demandProduct->getQuantity(),
                    'productUnit' => $productOffer->getProductUnit(),
                    'productUnitCode' => $productOffer->getProductUnitCode(),
                    'price' => $productOffer->getPrice(),
                    'fromExternalSource' => true,
                ];
            }
        }

        return $result;
    }
}
