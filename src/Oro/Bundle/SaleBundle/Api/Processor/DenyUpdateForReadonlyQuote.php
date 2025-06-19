<?php

namespace Oro\Bundle\SaleBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Denies updating read-only Quote entity.
 */
class DenyUpdateForReadonlyQuote implements ProcessorInterface
{
    public function __construct(
        private readonly array $readonlyStatuses,
        private readonly WorkflowRegistry $workflowRegistry
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var UpdateContext $context */

        if (!$this->workflowRegistry->hasActiveWorkflowsByEntityClass(Quote::class)) {
            return;
        }

        /** @var Quote $quote */
        $quote = $context->getResult();
        $quoteStatus = $quote->getInternalStatus()?->getInternalId();
        if ($quoteStatus && \in_array($quoteStatus, $this->readonlyStatuses, true)) {
            throw new AccessDeniedException(\sprintf('The quote marked as %s cannot be changed.', $quoteStatus));
        }
    }
}
