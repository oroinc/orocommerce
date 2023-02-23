<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ProductBundle\Api\Model\ProductCollection;
use Oro\Bundle\WebCatalogBundle\Api\Processor\FindContentVariantForSubresource;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\SerializationHelper;

/**
 * Loads product collection content variant data for "get_subresource" and "get_subresource" actions.
 */
class LoadProductCollectionContentVariantSubresource implements ProcessorInterface
{
    private const ID_FIELD = 'id';

    private SerializationHelper $serializationHelper;

    public function __construct(SerializationHelper $serializationHelper)
    {
        $this->serializationHelper = $serializationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SubresourceContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $context->setResult($this->getNormalizedData($context));

        $context->skipGroup(ApiActionGroup::NORMALIZE_DATA);
    }

    private function getNormalizedData(SubresourceContext $context): array
    {
        $item = [
            ConfigUtil::CLASS_NAME => ProductCollection::class,
            self::ID_FIELD         => $context->get(FindContentVariantForSubresource::CONTENT_ID)
        ];

        $items = $this->serializationHelper->processPostSerializeItems(
            [$item],
            $context->getConfig(),
            $context->getNormalizationContext()
        );

        return reset($items);
    }
}
