<?php

namespace Oro\Bundle\VisibilityBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Processor\Create\SetEntityIdToContext;
use Oro\Bundle\VisibilityBundle\Api\VisibilityIdUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Sets a created visibility entity identifier into the context.
 */
class SetVisibilityIdToContext implements ProcessorInterface
{
    public function __construct(
        private PropertyAccessorInterface $propertyAccessor
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CreateContext $context */

        if ($context->isProcessed(SetEntityIdToContext::OPERATION_NAME)) {
            // the entity identifier was already set
            return;
        }

        if ($context->isExisting()) {
            // the setting of an entity identifier to the context is needed only for a new entity
            return;
        }

        $entity = $context->getResult();
        if (null === $entity) {
            return;
        }

        $visibilityId = [];
        $idFieldConfig = $context->getConfig()->getField('id');
        $propertyPaths = $idFieldConfig->getDependsOn();
        foreach ($propertyPaths as $propertyPath) {
            $visibilityId[$propertyPath] = $this->propertyAccessor->getValue($entity, $propertyPath);
        }
        $context->setId(VisibilityIdUtil::encodeVisibilityId($visibilityId, $idFieldConfig));
        $context->setProcessed(SetEntityIdToContext::OPERATION_NAME);
    }
}
