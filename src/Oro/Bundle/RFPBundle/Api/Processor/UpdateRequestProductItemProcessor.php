<?php

namespace Oro\Bundle\RFPBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class UpdateRequestProductItemProcessor implements ProcessorInterface
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

        $this->processRequestData($productItem, $requestData);
        $context->setRequestData($requestData);
    }

    /**
     * @param RequestProductItem $productItem
     * @param array $requestData
     */
    protected function processRequestData(RequestProductItem $productItem, array &$requestData)
    {
        $currency = $productItem->getPrice()->getCurrency();
        $value = $productItem->getPrice()->getValue();

        if (array_key_exists('currency', $requestData)) {
            $currency = $requestData['currency'];
            unset($requestData['currency']);
        }

        if (array_key_exists('value', $requestData)) {
            $value = $requestData['value'];
            unset($requestData['value']);
        }

        $productItem->setPrice(Price::create($value, $currency));
    }
}
