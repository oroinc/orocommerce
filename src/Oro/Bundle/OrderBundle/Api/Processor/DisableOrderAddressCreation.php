<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Disables "create" action for an order address resource if it is executed as a master request.
 */
class DisableOrderAddressCreation implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->isMasterRequest()) {
            throw new AccessDeniedException(
                'Use API resource to create an order. An order address can be created only together with an order.'
            );
        }
    }
}
