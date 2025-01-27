<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\CheckoutBundle\Api\Repository\CheckoutLineItemGroupRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "lineItemGroups" field for Checkout entity.
 */
class ComputeCheckoutLineItemGroups implements ProcessorInterface
{
    private const string LINE_ITEM_GROUPS_FIELD_NAME = 'lineItemGroups';

    public function __construct(
        private readonly CheckoutLineItemGroupRepository $checkoutLineItemGroupRepository,
        private readonly ObjectNormalizer $objectNormalizer
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();
        if (!$context->isFieldRequested(self::LINE_ITEM_GROUPS_FIELD_NAME, $data)) {
            return;
        }

        $config = $context->getConfig();
        $groupConfig = $config->getField(self::LINE_ITEM_GROUPS_FIELD_NAME)->getTargetEntity();
        $normalizationContext = $context->getNormalizationContext();
        $groupIds = $this->checkoutLineItemGroupRepository->getGroupIds(
            $data[$context->getResultFieldName('id', $config)],
            $context->getRequestType()
        );
        $groupsData = [];
        foreach ($groupIds as $groupId) {
            $groupsData[] = $this->normalizeLineItemGroup(
                $groupId,
                $context->getResultFieldName('id', $groupConfig),
                $groupConfig,
                $normalizationContext
            );
        }
        $data[self::LINE_ITEM_GROUPS_FIELD_NAME] = $groupsData;
        $context->setData($data);
    }

    private function normalizeLineItemGroup(
        string $groupId,
        string $groupIdFieldName,
        EntityDefinitionConfig $groupConfig,
        array $normalizationContext
    ): array {
        if ($groupConfig->isIdentifierOnlyRequested()) {
            return [$groupIdFieldName => $groupId];
        }

        $group = $this->checkoutLineItemGroupRepository->findGroup($groupId);
        if (null === $group) {
            return [$groupIdFieldName => $groupId];
        }

        $normalizedData = $this->objectNormalizer->normalizeObjects([$group], $groupConfig, $normalizationContext);

        return $normalizedData[0];
    }
}
