<?php

namespace Oro\Bundle\WebCatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Provider\ContentNodeProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Finds the web catalog node content variant appropriate for the current logged in user
 * and add the found content class and its ID to "contentClass" and "contentId" attributes of the context.
 */
class FindContentVariantForSubresource implements ProcessorInterface
{
    public const CONTENT_CLASS = 'contentClass';
    public const CONTENT_ID = 'contentId';

    private ContentVariantTypeRegistry $contentVariantTypeRegistry;
    private ContentNodeProvider $contentNodeProvider;

    public function __construct(
        ContentVariantTypeRegistry $contentVariantTypeRegistry,
        ContentNodeProvider $contentNodeProvider
    ) {
        $this->contentVariantTypeRegistry = $contentVariantTypeRegistry;
        $this->contentNodeProvider = $contentNodeProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SubresourceContext $context */

        if ($context->has(self::CONTENT_CLASS)) {
            return;
        }

        [$contentClass, $contentId] = $this->getContentVariant($context->getParentId());
        if (!$contentClass) {
            throw new NotFoundHttpException('The content variant was not found.');
        }

        $context->set(self::CONTENT_CLASS, $contentClass);
        $context->set(self::CONTENT_ID, $contentId);
    }

    /**
     * @param int $nodeId
     *
     * @return array|null [content class, content id]
     */
    private function getContentVariant(int $nodeId): ?array
    {
        $contentVariantFields = [ContentNodeProvider::ENTITY_ALIAS_PLACEHOLDER . '.type' => 'type'];
        $contentVariantTypes = $this->contentVariantTypeRegistry->getContentVariantTypes();
        foreach ($contentVariantTypes as $contentVariantType) {
            $expr = $contentVariantType->getApiResourceIdentifierDqlExpression(
                ContentNodeProvider::ENTITY_ALIAS_PLACEHOLDER
            );

            //Empty getApiResourceIdentifierDqlExpression means ContentVariantType is not enabled for API and should not
            //be added to the query
            if (!$expr) {
                continue;
            }

            $contentVariantFields[$expr] = $contentVariantType->getName();
        }

        $details = $this->contentNodeProvider->getContentVariantDetails([$nodeId], $contentVariantFields);
        if (!isset($details[$nodeId])) {
            return [null, null];
        }

        $detail = $details[$nodeId];
        $type = $this->contentVariantTypeRegistry->getContentVariantType($detail['type']);

        return [$type->getApiResourceClassName(), $detail[$type->getName()]];
    }
}
