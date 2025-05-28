<?php

namespace Oro\Bundle\SaleBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Marks "internal_status" field for Quote entity as read-only by disabling form mapping for it
 * when there is an active workflow for quotes.
 */
class ConfigureQuoteInternalStatusField implements ProcessorInterface
{
    public function __construct(
        private readonly WorkflowRegistry $workflowRegistry
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        if ($this->workflowRegistry->hasActiveWorkflowsByEntityClass(Quote::class)) {
            $context->getResult()->findField('internal_status', true)?->setFormOption('mapped', false);
        }
    }
}
