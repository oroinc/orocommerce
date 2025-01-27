<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\CheckoutBundle\Api\Repository\CheckoutLineItemGroupRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Loads a checkout line item group.
 */
class LoadCheckoutLineItemGroup implements ProcessorInterface
{
    public function __construct(
        private readonly CheckoutLineItemGroupRepository $checkoutLineItemGroupRepository
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $group = $this->checkoutLineItemGroupRepository->findGroup($context->getId());
        if (null === $group) {
            throw new NotFoundHttpException();
        }

        $context->setResult($group);
    }
}
