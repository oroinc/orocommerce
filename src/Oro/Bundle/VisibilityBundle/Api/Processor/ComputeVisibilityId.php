<?php

namespace Oro\Bundle\VisibilityBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\VisibilityBundle\Api\VisibilityIdUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets ID for a visibility entity to the response data.
 */
class ComputeVisibilityId implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        $idFieldConfig = $context->getConfig()->getField('id');

        $visibilityId = [];
        $propertyPaths = $idFieldConfig->getDependsOn();
        foreach ($propertyPaths as $propertyPath) {
            $visibilityId[$propertyPath] = $context->getResultFieldValueByPropertyPath($propertyPath, $data);
        }

        $data['id'] = VisibilityIdUtil::encodeVisibilityId($visibilityId, $idFieldConfig);
        $context->setData($data);
    }
}
