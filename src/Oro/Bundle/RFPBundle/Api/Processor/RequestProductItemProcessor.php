<?php

namespace Oro\Bundle\RFPBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class RequestProductItemProcessor implements ProcessorInterface
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

        if (!$requestData || !$productItem instanceof RequestProductItem) {
            return;
        }

        $context->setRequestData($this->processRequestData($productItem, $requestData));
    }

    /**
     * @param RequestProductItem $productItem
     * @param array $requestData
     * @return array
     */
    protected function processRequestData(RequestProductItem $productItem, array $requestData)
    {
        $currency = $productItem->getPrice() ? $productItem->getPrice()->getCurrency() : null;
        $value =  $productItem->getPrice() ? $productItem->getPrice()->getValue() : null;

        if (array_key_exists('currency', $requestData)) {
            $currency = $requestData['currency'];
        }

        if (array_key_exists('value', $requestData)) {
            $value = $requestData['value'];
        }

        if (null !== $currency && null !== $value) {
            $productItem->setPrice(Price::create($value, $currency));
            $requestData['currency'] = $currency;
            $requestData['value'] = $value;
        }

        return $requestData;
    }
}
