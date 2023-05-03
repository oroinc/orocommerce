<?php

namespace Oro\Bundle\WebCatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\WebCatalogBundle\Api\Model\SystemPage;
use Oro\Bundle\WebCatalogBundle\Api\Repository\SystemPageRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Expands data for "system page" content variants.
 */
class ExpandSystemPageContentVariant implements ProcessorInterface
{
    private const URL_FIELD = 'url';

    private ObjectNormalizer $objectNormalizer;
    private SystemPageRepository $systemPageRepository;

    public function __construct(
        ObjectNormalizer $objectNormalizer,
        SystemPageRepository $systemPageRepository
    ) {
        $this->objectNormalizer = $objectNormalizer;
        $this->systemPageRepository = $systemPageRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();
        if (!$context->isFieldRequestedForCollection($context->getResultFieldName(self::URL_FIELD), $data)) {
            return;
        }

        $targetEntityIdFieldName = $this->getTargetEntityIdFieldName($context);
        $normalizedData = $this->objectNormalizer->normalizeObjects(
            $this->getSystemPages($data, $targetEntityIdFieldName),
            $context->getConfig(),
            $context->getNormalizationContext(),
            true
        );

        $context->setData(
            $this->buildResultData($data, $normalizedData, $targetEntityIdFieldName)
        );
    }

    private function getTargetEntityIdFieldName(CustomizeLoadedDataContext $context): string
    {
        $idFieldNames = $context->getConfig()->getIdentifierFieldNames();

        return $context->getResultFieldName(reset($idFieldNames));
    }

    /**
     * @param array  $data
     * @param string $targetEntityIdFieldName
     *
     * @return SystemPage[]
     */
    private function getSystemPages(array $data, string $targetEntityIdFieldName): array
    {
        $entities = [];
        foreach ($data as $item) {
            $entity = $this->systemPageRepository->findSystemPage($item[$targetEntityIdFieldName]);
            if (null !== $entity) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    private function buildResultData(array $data, array $normalizedData, string $targetEntityIdFieldName): array
    {
        $normalizedDataMap = [];
        foreach ($normalizedData as $item) {
            $normalizedDataMap[$item[$targetEntityIdFieldName]] = $item;
        }

        $resultData = [];
        foreach ($data as $key => $item) {
            $resultData[$key] = $normalizedDataMap[$item[$targetEntityIdFieldName]] ?? $item;
        }

        return $resultData;
    }
}
