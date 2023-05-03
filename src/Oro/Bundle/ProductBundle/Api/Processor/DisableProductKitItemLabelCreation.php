<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Disables "create" action for a product kit item label resource if it is executed as a master request.
 */
class DisableProductKitItemLabelCreation implements ProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->isMasterRequest()) {
            throw new AccessDeniedException(
                'Use API resource to create a product kit item. A product kit item label can be created only '
                . 'together with a product kit item.'
            );
        }
    }
}
