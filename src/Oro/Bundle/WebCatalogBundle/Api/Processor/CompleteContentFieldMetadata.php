<?php

namespace Oro\Bundle\WebCatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets the list of supported targets for "content" field for ContentNode entity.
 */
class CompleteContentFieldMetadata implements ProcessorInterface
{
    private const CONTENT_FIELD_NAME = 'content';

    private ContentVariantTypeRegistry $contentVariantTypeRegistry;

    public function __construct(ContentVariantTypeRegistry $contentVariantTypeRegistry)
    {
        $this->contentVariantTypeRegistry = $contentVariantTypeRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var MetadataContext $context */

        $entityMetadata = $context->getResult();
        if (null === $entityMetadata) {
            return;
        }

        $association = $entityMetadata->getAssociation(self::CONTENT_FIELD_NAME);
        if (null === $association) {
            return;
        }

        $classNames = [];
        $contentVariantTypes = $this->contentVariantTypeRegistry->getContentVariantTypes();
        foreach ($contentVariantTypes as $contentVariantType) {
            $className = $contentVariantType->getApiResourceClassName();
            if ($className) {
                $classNames[] = $className;
            }
        }
        $association->setAcceptableTargetClassNames($classNames);
    }
}
