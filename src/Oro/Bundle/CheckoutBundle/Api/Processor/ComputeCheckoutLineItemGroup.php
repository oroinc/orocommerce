<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\CheckoutBundle\Api\Repository\CheckoutLineItemGroupRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "group" field for CheckoutLineItem entity.
 */
class ComputeCheckoutLineItemGroup implements ProcessorInterface
{
    private const string GROUP_FIELD_NAME = 'group';

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
        if (!$context->isFieldRequested(self::GROUP_FIELD_NAME, $data)) {
            return;
        }

        $config = $context->getConfig();
        $checkoutFieldName = $context->getResultFieldName('checkout', $config);
        $checkoutConfig = $config->getField($checkoutFieldName)->getTargetEntity();
        $groupConfig = $config->getField(self::GROUP_FIELD_NAME)->getTargetEntity();
        $normalizationContext = $context->getNormalizationContext();
        $groupId = $this->checkoutLineItemGroupRepository->getGroupId(
            $data[$checkoutFieldName][$context->getResultFieldName('id', $checkoutConfig)],
            $data[$context->getResultFieldName('id', $config)],
            $context->getRequestType()
        );
        $groupData = null;
        if (null !== $groupId) {
            $groupData = $this->normalizeLineItemGroup(
                $groupId,
                $context->getResultFieldName('id', $groupConfig),
                $groupConfig,
                $normalizationContext
            );
        }
        $data[self::GROUP_FIELD_NAME] = $groupData;
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
