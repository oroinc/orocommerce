<?php

namespace Oro\Bundle\OrderBundle\ImportExport\Writer\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets validation groups that should be used to validate entities during the import of external orders.
 */
class SetEntityValidationGroups implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var FormContext $context */

        $context->setFormOptions(['validation_groups' => ['external_order_import', 'api']]);
    }
}
