<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\CheckoutBundle\Api\Repository\CheckoutLineItemGroupRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets values for "shippingMethod" and "shippingMethodType" to NULL
 * when the checkout shipping type in not equal to the given shipping type.
 */
class ComputeCheckoutShippingMethod implements ProcessorInterface
{
    public function __construct(
        private readonly CheckoutLineItemGroupRepository $checkoutLineItemGroupRepository,
        private readonly string $shippingType
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        if ($this->checkoutLineItemGroupRepository->getShippingType() === $this->shippingType) {
            return;
        }

        $data = $context->getData();
        $shippingMethodFieldName = $context->getResultFieldName('shippingMethod');
        if ($context->isFieldRequested($shippingMethodFieldName) && null !== $data[$shippingMethodFieldName]) {
            $data[$shippingMethodFieldName] = null;
        }
        $shippingMethodTypeFieldName = $context->getResultFieldName('shippingMethodType');
        if ($context->isFieldRequested($shippingMethodTypeFieldName) && null !== $data[$shippingMethodTypeFieldName]) {
            $data[$shippingMethodTypeFieldName] = null;
        }
        $context->setData($data);
    }
}
