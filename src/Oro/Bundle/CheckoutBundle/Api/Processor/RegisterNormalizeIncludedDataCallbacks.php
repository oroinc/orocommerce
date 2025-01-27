<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\NormalizeIncludedData;
use Oro\Bundle\CheckoutBundle\Api\Model\CheckoutLineItemGroup;
use Oro\Bundle\CheckoutBundle\Api\Repository\CheckoutLineItemGroupRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Registers a callback function that should be used to normalize checkout line item groups
 * in the included data.
 */
class RegisterNormalizeIncludedDataCallbacks implements ProcessorInterface
{
    public function __construct(
        private readonly CheckoutLineItemGroupRepository $checkoutLineItemGroupRepository
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var FormContext $context */

        NormalizeIncludedData::registerNormalizeIncludedDataCallback(
            $context,
            CheckoutLineItemGroup::class,
            function (mixed $entityIdOrCriteria): ?CheckoutLineItemGroup {
                return \is_string($entityIdOrCriteria)
                    ? $this->checkoutLineItemGroupRepository->findGroup($entityIdOrCriteria)
                    : null;
            }
        );
    }
}
