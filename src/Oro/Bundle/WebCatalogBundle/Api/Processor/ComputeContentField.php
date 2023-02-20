<?php

namespace Oro\Bundle\WebCatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Provider\ContentNodeProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "content" field for ContentNode entity.
 */
class ComputeContentField implements ProcessorInterface
{
    private const CONTENT_FIELD_NAME = 'content';

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
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();
        if (!$context->isFieldRequestedForCollection(self::CONTENT_FIELD_NAME, $data)) {
            return;
        }

        $nodeIdFieldName = $context->getResultFieldName('id');
        $variantIdField = $context->getConfig()
            ->getField(self::CONTENT_FIELD_NAME)
            ->getTargetEntity()
            ->findFieldNameByPropertyPath('id');

        $variantsData = $this->getContentVariants($context->getIdentifierValues($data, $nodeIdFieldName));
        foreach ($data as $key => $item) {
            if (!isset($variantsData[$item[$nodeIdFieldName]])) {
                continue;
            }

            [$contentClass, $contentId] = $variantsData[$item[$nodeIdFieldName]];
            $data[$key][self::CONTENT_FIELD_NAME] = [
                ConfigUtil::CLASS_NAME => $contentClass,
                $variantIdField        => $contentId
            ];
        }

        $context->setData($data);
    }

    /**
     * @param array $nodeIds
     *
     * @return array [node id => [content class, content id], ...]
     */
    private function getContentVariants(array $nodeIds): array
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

        $details = $this->contentNodeProvider->getContentVariantDetails($nodeIds, $contentVariantFields);

        $result = [];
        foreach ($details as $nodeId => $detail) {
            $type = $this->contentVariantTypeRegistry->getContentVariantType($detail['type']);
            if (!isset($detail[$type->getName()])) {
                continue;
            }

            $result[$nodeId] = [$type->getApiResourceClassName(), $detail[$type->getName()]];
        }

        return $result;
    }
}
