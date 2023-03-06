<?php

namespace Oro\Bundle\VisibilityBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\VisibilityBundle\Api\VisibilityIdHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets ID for a visibility entity to the response data.
 */
class ComputeVisibilityId implements ProcessorInterface
{
    private VisibilityIdHelper $visibilityIdHelper;

    public function __construct(VisibilityIdHelper $visibilityIdHelper)
    {
        $this->visibilityIdHelper = $visibilityIdHelper;
    }

    /**
     * {@inheritdoc}
     */
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

        $data['id'] = $this->visibilityIdHelper->encodeVisibilityId($visibilityId, $idFieldConfig);
        $context->setData($data);
    }
}
