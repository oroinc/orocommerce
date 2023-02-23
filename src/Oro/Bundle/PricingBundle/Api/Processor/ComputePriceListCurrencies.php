<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "currencies" field for PriceList entity.
 */
class ComputePriceListCurrencies implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $fieldName = $context->getResultFieldName('currencies');
        if (!$context->isFieldRequested($fieldName)) {
            return;
        }

        $data = $context->getData();
        $rawCurrencies = $data[$fieldName];
        if (!$rawCurrencies || !\is_array(reset($rawCurrencies))) {
            return;
        }

        $currencies = [];
        foreach ($rawCurrencies as $item) {
            $currencies[] = $item['currency'];
        }
        sort($currencies);
        $data[$fieldName] = $currencies;

        $context->setData($data);
    }
}
