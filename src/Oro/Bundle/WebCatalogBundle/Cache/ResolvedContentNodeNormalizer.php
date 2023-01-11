<?php

namespace Oro\Bundle\WebCatalogBundle\Cache;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Cache\Normalizer\LocalizedFallbackValueNormalizer;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Factory\ResolvedContentNodeFactory;
use Oro\Bundle\WebCatalogBundle\Exception\InvalidArgumentException;

/**
 * Normalizes {@see ResolvedContentNode} for usage in cache.
 */
class ResolvedContentNodeNormalizer
{
    private DoctrineHelper $doctrineHelper;

    private LocalizedFallbackValueNormalizer $localizedFallbackValueNormalizer;

    private ResolvedContentNodeFactory $resolvedContentNodeFactory;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        LocalizedFallbackValueNormalizer $localizedFallbackValueNormalizer,
        ResolvedContentNodeFactory $resolvedContentNodeFactory
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->localizedFallbackValueNormalizer = $localizedFallbackValueNormalizer;
        $this->resolvedContentNodeFactory = $resolvedContentNodeFactory;
    }

    /**
     * @param ResolvedContentNode $resolvedNode
     * @param array $context
     *
     * @return array
     */
    public function normalize(ResolvedContentNode $resolvedNode, array $context = []): array
    {
        return [
            'id' => $resolvedNode->getId(),
            'priority' => $resolvedNode->getPriority(),
            'identifier' => $resolvedNode->getIdentifier(),
            'rewriteVariantTitle' => $resolvedNode->isRewriteVariantTitle(),
            'titles' => array_map(
                [$this->localizedFallbackValueNormalizer, 'normalize'],
                $resolvedNode->getTitles()->toArray()
            ),
            'contentVariant' => $this->normalizeResolvedContentVariant($resolvedNode->getResolvedContentVariant()),
            'childNodes' => $this->normalizeArray($resolvedNode->getChildNodes()),
        ];
    }

    /**
     * @param array $data
     * @param array $context Available context options:
     *  [
     *      'tree_depth' => int, // Restricts the maximum tree depth. -1 stands for unlimited.
     *  ]
     *
     * @return ResolvedContentNode|null
     */
    public function denormalize(array $data, array $context = []): ?ResolvedContentNode
    {
        return $this->doDenormalize($data, $context['tree_depth'] ?? -1);
    }

    private function doDenormalize(array $data, int $treeDepth): ResolvedContentNode
    {
        if (!isset($data['id'], $data['identifier'])) {
            throw new InvalidArgumentException(
                'Elements "id", "identifier" are required for the denormalization of ResolvedContentNode'
            );
        }

        $resolvedNode = $this->resolvedContentNodeFactory->createFromArray($data);

        if ($treeDepth === 0) {
            return $resolvedNode;
        }

        $treeDepth--;

        foreach ($data['childNodes'] ?? [] as $childNodeData) {
            $resolvedNode->addChildNode($this->doDenormalize($childNodeData, $treeDepth));
        }

        return $resolvedNode;
    }

    private function normalizeResolvedContentVariant(ResolvedContentVariant $resolvedVariant): array
    {
        $normalized = $this->normalizeArray($resolvedVariant->getData(), true);
        $normalized['slugs'] = [];
        foreach ($resolvedVariant->getLocalizedUrls() as $localizedFallbackValue) {
            $normalized['slugs'][] = [
                'url' => $localizedFallbackValue->getString() ?: $localizedFallbackValue->getText(),
                'localization' => $this->getEntityReference($localizedFallbackValue->getLocalization()),
                'fallback' => $localizedFallbackValue->getFallback(),
            ];
        }

        return $normalized;
    }

    private function getEntityReference($object): ?array
    {
        if ($object === null) {
            return null;
        }

        return [
            'class' => $this->doctrineHelper->getEntityClass($object),
            'id' => $this->doctrineHelper->getSingleEntityIdentifier($object),
        ];
    }

    private function normalizeArray(iterable $traversable, bool $skipNulls = false): array
    {
        $data = [];
        foreach ($traversable as $key => $value) {
            if ($skipNulls && $value === null) {
                continue;
            }

            if (is_object($value)) {
                $value = $this->convertObject($value);
            } elseif (is_array($value)) {
                $value = $this->normalizeArray($value);
            }

            $data[$key] = $value;
        }

        return $data;
    }

    private function convertObject($value): ?array
    {
        if ($value instanceof LocalizedFallbackValue) {
            return $this->localizedFallbackValueNormalizer->normalize($value);
        }

        if ($value instanceof ResolvedContentNode) {
            return $this->normalize($value);
        }

        if ($value instanceof \Traversable) {
            return $this->normalizeArray($value);
        }

        if ($this->doctrineHelper->isManageableEntity($value)) {
            return $this->getEntityReference($value);
        }

        return null;
    }
}
