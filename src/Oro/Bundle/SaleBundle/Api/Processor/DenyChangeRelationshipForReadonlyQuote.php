<?php

namespace Oro\Bundle\SaleBundle\Api\Processor;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition\UnidirectionalAssociationCompleter;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Denies relationships that change data for read-only Quote entity.
 */
class DenyChangeRelationshipForReadonlyQuote implements ProcessorInterface
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

        /** @var Quote $quote */
        $quote = $context->getParentEntity();
        $quoteStatus = $quote->getInternalStatus()?->getInternalId();
        if (
            $quoteStatus
            && \in_array($quoteStatus, $this->readonlyStatuses, true)
            && !$this->isActivityAssociation(
                $context->getParentConfig(),
                $context->getParentClassName(),
                $context->getAssociationName()
            )
        ) {
            throw new AccessDeniedException(\sprintf('The quote marked as %s cannot be changed.', $quoteStatus));
        }
    }

    private function isActivityAssociation(
        EntityDefinitionConfig $entityConfig,
        string $entityClass,
        string $associationName
    ): bool {
        $associationPropertyName = $entityConfig->getField($associationName)?->getPropertyPath($associationName);
        if (!$associationPropertyName || ConfigUtil::IGNORE_PROPERTY_PATH !== $associationPropertyName) {
            return false;
        }

        $unidirectionalAssociations = $entityConfig->get(
            UnidirectionalAssociationCompleter::UNIDIRECTIONAL_ASSOCIATIONS
        );
        if (!$unidirectionalAssociations || !isset($unidirectionalAssociations[$associationName])) {
            return false;
        }

        $activityAssociationPropertyName = ExtendHelper::buildAssociationName(
            $entityClass,
            ActivityScope::ASSOCIATION_KIND
        );

        return $unidirectionalAssociations[$associationName] === $activityAssociationPropertyName;
    }
}
