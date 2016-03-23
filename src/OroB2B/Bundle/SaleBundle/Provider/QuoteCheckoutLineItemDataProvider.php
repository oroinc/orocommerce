<?php

namespace OroB2B\Bundle\SaleBundle\Provider;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Component\Checkout\DataProvider\CheckoutDataProviderInterface;

class QuoteCheckoutLineItemDataProvider implements CheckoutDataProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getData($transformData)
    {
        /** @var QuoteProductOffer[] $transformData */
        return $this->isTransformDataSupported($transformData) ? $this->prepareData($transformData) : [];
    }

    /**
     * {@inheritdoc}
     */
    public function isTransformDataSupported($transformData)
    {
        if (!is_array($transformData)) {
            return false;
        }

        foreach ($transformData as $item) {
            if (!$item instanceof QuoteProductOffer) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param QuoteProductOffer[] $data
     * @return array
     */
    protected function prepareData($data)
    {
        $result = [];
        foreach ($data as $productOffer) {
            $result[] = [
                'product' => $productOffer->getProduct(),
                'productSku' => $productOffer->getProductSku(),
                'quantity' => $productOffer->getQuantity(),
                'productUnit' => $productOffer->getProductUnit(),
                'freeFromProduct' => $productOffer->getQuoteProduct()->getFreeFormProduct(),
                'productUnitCode' => $productOffer->getProductUnitCode(),
                'price' => $productOffer->getPrice()
            ];
        }

        return $result;
    }
}
