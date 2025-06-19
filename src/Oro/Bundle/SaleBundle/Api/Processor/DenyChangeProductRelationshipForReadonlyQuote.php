<?php

namespace Oro\Bundle\SaleBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Denies relationships that change data for read-only Quote Product entity.
 */
class DenyChangeProductRelationshipForReadonlyQuote implements ProcessorInterface
{
    public function __construct(
        private readonly array $readonlyStatuses,
        private readonly WorkflowRegistry $workflowRegistry
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ChangeRelationshipContext $context */

        if (!$this->workflowRegistry->hasActiveWorkflowsByEntityClass(Quote::class)) {
            return;
        }

        /** @var QuoteProduct $quoteProduct */
        $quoteProduct = $context->getParentEntity();
        $quoteStatus = $quoteProduct->getQuote()?->getInternalStatus()?->getInternalId();
        if ($quoteStatus && \in_array($quoteStatus, $this->readonlyStatuses, true)) {
            throw new AccessDeniedException(\sprintf('The quote marked as %s cannot be changed.', $quoteStatus));
        }
    }
}
