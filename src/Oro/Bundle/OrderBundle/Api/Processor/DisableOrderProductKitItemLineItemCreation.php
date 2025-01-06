<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Disables "create" action for an order product kit item line item resource if it is executed as the main request.
 */
class DisableOrderProductKitItemLineItemCreation implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        throw new AccessDeniedException(
            'Use API resource to create an order.'
            . ' An order product kit item line item can be created only together with an order.'
        );
    }
}
