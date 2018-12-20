<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets "order" as the default value of "type" property for objects in "discounts" collection of Order entity.
 */
class SetOrderDefaultDiscountType implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getResult();
        if (!is_array($data)) {
            return;
        }

        $discountsFieldName = $context->getResultFieldName('discounts');
        if (!$context->isFieldRequested($discountsFieldName)) {
            return;
        }

        if (!empty($data[$discountsFieldName])) {
            foreach ($data[$discountsFieldName] as $key => $item) {
                if (empty($item['type'])) {
                    $data[$discountsFieldName][$key]['type'] = 'order';
                }
            }
            $context->setResult($data);
        }
    }
}
