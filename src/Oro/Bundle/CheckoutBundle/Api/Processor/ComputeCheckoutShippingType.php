<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\CheckoutBundle\Api\Repository\CheckoutLineItemGroupRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "shippingType" field for Checkout entity.
 */
class ComputeCheckoutShippingType implements ProcessorInterface
{
    private const string SHIPPING_TYPE_FIELD_NAME = 'shippingType';

    public function __construct(
        private readonly CheckoutLineItemGroupRepository $checkoutLineItemGroupRepository
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();
        if (!$context->isFieldRequested(self::SHIPPING_TYPE_FIELD_NAME, $data)) {
            return;
        }

        $data[self::SHIPPING_TYPE_FIELD_NAME] = $this->checkoutLineItemGroupRepository->getShippingType();
        $context->setData($data);
    }
}
