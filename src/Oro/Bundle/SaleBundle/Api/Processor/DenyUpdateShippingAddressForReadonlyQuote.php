<?php

namespace Oro\Bundle\SaleBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Denies updating of shipping address for read-only Quote entity.
 */
class DenyUpdateShippingAddressForReadonlyQuote implements ProcessorInterface
{
    public function __construct(
        private readonly array $readonlyStatuses,
        private readonly WorkflowRegistry $workflowRegistry,
        private readonly DoctrineHelper $doctrineHelper,
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

        $quoteStatus = $this->getQuote($context)?->getInternalStatus()?->getInternalId();
        if ($quoteStatus && \in_array($quoteStatus, $this->readonlyStatuses, true)) {
            throw new AccessDeniedException(\sprintf('The quote marked as %s cannot be changed.', $quoteStatus));
        }
    }

    private function getQuote(FormContext $context): ?Quote
    {
        if (!$context->isExisting()) {
            return $context->getForm()->has('quote')
                ? $context->getForm()->get('quote')->getData()
                : null;
        }

        return $this->doctrineHelper->createQueryBuilder(Quote::class, 'q')
            ->where('q.shippingAddress = :addressId')
            ->setParameter('addressId', $context->getResult()->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }
}
