<?php

namespace Oro\Bundle\SaleBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Denies updating of product for read-only Quote entity.
 */
class DenyUpdateProductForReadonlyQuote implements ProcessorInterface
{
    public function __construct(
        private readonly array $readonlyStatuses,
        private readonly WorkflowRegistry $workflowRegistry,
        private readonly bool $checkExistingEntity
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var FormContext $context */

        if ($context->isExisting() !== $this->checkExistingEntity) {
            return;
        }

        if (!$this->workflowRegistry->hasActiveWorkflowsByEntityClass(Quote::class)) {
            return;
        }

        /** @var QuoteProduct $quoteProduct */
        $quoteProduct = $context->getResult();
        $quoteStatus = $quoteProduct->getQuote()?->getInternalStatus()?->getInternalId();
        if ($quoteStatus && \in_array($quoteStatus, $this->readonlyStatuses, true)) {
            throw new AccessDeniedException(\sprintf('The quote marked as %s cannot be changed.', $quoteStatus));
        }
    }
}
