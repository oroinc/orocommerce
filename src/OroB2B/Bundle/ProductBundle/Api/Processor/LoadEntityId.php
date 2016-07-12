<?php

namespace OroB2B\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class LoadEntityId implements ProcessorInterface
{
    const METHOD = 'getId';

    public function process(ContextInterface $context)
    {
        /** @var SingleItemContext $context */

        $entityId = $context->getId();
        if (null !== $entityId) {
            return;
        }

        $entity = $context->getResult();
        if (is_object($entity) && method_exists($entity, self::METHOD)) {
            $method = self::METHOD;
            $context->setId($entity->$method());
        }
    }
}
