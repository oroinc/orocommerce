<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Disables "create" action for a product kit item label resource if it is executed as the main request.
 */
class DisableProductKitItemLabelCreation implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        throw new AccessDeniedException(
            'Use API resource to create a product kit item. A product kit item label can be created only '
            . 'together with a product kit item.'
        );
    }
}
