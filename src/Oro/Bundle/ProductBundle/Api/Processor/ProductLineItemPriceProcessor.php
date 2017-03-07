<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceSetterAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class ProductLineItemPriceProcessor implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        if (!$context instanceof FormContext) {
            return;
        }

        $requestData = $context->getRequestData();
        $productItem = $context->getResult();

        if (!$requestData
            || false === ($productItem instanceof ProductLineItemInterface)
            || false === ($productItem instanceof PriceSetterAwareInterface)
        ) {
            return;
        }

        $context->setRequestData($this->processRequestData($productItem, $requestData));
    }

    /**
     * @param PriceSetterAwareInterface $priceSetterAwareItem
     * @param array                     $requestData
     *
     * @return array
     */
    protected function processRequestData(PriceSetterAwareInterface $priceSetterAwareItem, array $requestData)
    {
        $currency = null;
        $value = null;

        if ($priceSetterAwareItem->getPrice()) {
            $currency = $priceSetterAwareItem->getPrice()->getCurrency();
            $value = $priceSetterAwareItem->getPrice()->getValue();
        }

        if (array_key_exists('currency', $requestData)) {
            $currency = $requestData['currency'];
        }

        if (array_key_exists('value', $requestData)) {
            $value = $requestData['value'];
        }

        if (null === $currency || null === $value) {
            return $requestData;
        }

        $priceSetterAwareItem->setPrice(Price::create($value, $currency));

        $requestData['currency'] = $currency;
        $requestData['value'] = $value;

        return $requestData;
    }
}
