<?php

namespace Oro\Bundle\WebCatalogBundle\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\Exception\InvalidArgumentException;

/**
 * The cache for web catalog content node tree.
 */
class ResolvedContentNodeNormalizer
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
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
            'resolveVariantTitle' => $resolvedNode->isRewriteVariantTitle(),
            'titles' => $this->normalizeLocalizedValuesArray($resolvedNode->getTitles()),
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

        $this->resolveReferences($data);

        $resolvedVariant = new ResolvedContentVariant();
        $resolvedVariant->setData($data['contentVariant']['data'] ?? []);

        foreach ($data['contentVariant']['localizedUrls'] ?? [] as $localizedUrl) {
            $resolvedVariant->addLocalizedUrl($this->getLocalizedValue($localizedUrl));
        }

        $titles = new ArrayCollection();
        foreach ($data['titles'] ?? [] as $title) {
            $titles->add($this->getLocalizedValue($title));
        }

        $resolvedNode = new ResolvedContentNode(
            $data['id'],
            $data['identifier'],
            $data['priority'] ?? 0,
            $titles,
            $resolvedVariant,
            $data['resolveVariantTitle'] ?? true
        );

        if ($treeDepth === 0) {
            return $resolvedNode;
        }

        $treeDepth--;

        foreach ($data['childNodes'] ?? [] as $childNodeData) {
            $resolvedNode->addChildNode($this->doDenormalize($childNodeData, $treeDepth));
        }

        return $resolvedNode;
    }

    private function resolveReferences(array &$data): void
    {
        foreach ($data as $key => &$value) {
            if ($key === 'childNodes') {
                continue;
            }

            if (is_array($value)) {
                if (array_key_exists('entity_class', $value)) {
                    $value = $this->doctrineHelper->getEntityReference($value['entity_class'], $value['entity_id']);
                } else {
                    $this->resolveReferences($value);
                }
            }
        }
    }

    private function getLocalizedValue(array $localizedData): LocalizedFallbackValue
    {
        if (!isset($localizedData['string'])) {
            throw new InvalidArgumentException(
                'Element "string" is required for the denormalization of title for ResolvedContentNode'
            );
        }

        $value = new LocalizedFallbackValue();
        $value->setString($localizedData['string']);
        $value->setLocalization($localizedData['localization'] ?? null);
        $value->setFallback($localizedData['fallback'] ?? null);

        return $value;
    }

    private function normalizeLocalizedFallbackValue(LocalizedFallbackValue $value): array
    {
        return [
            'string' => $value->getString() ?: $value->getText(),
            'localization' => $this->getEntityReference($value->getLocalization()),
            'fallback' => $value->getFallback(),
        ];
    }

    private function normalizeResolvedContentVariant(ResolvedContentVariant $resolvedVariant): array
    {
        return [
            'data' => $this->normalizeArray($resolvedVariant->getData(), true),
            'localizedUrls' => $this->normalizeArray($resolvedVariant->getLocalizedUrls()),
        ];
    }

    private function getEntityReference($object): ?array
    {
        if ($object === null) {
            return null;
        }

        return [
            'entity_class' => $this->doctrineHelper->getEntityClass($object),
            'entity_id' => $this->doctrineHelper->getSingleEntityIdentifier($object),
        ];
    }

    private function normalizeLocalizedValuesArray(Collection $values): array
    {
        return $this->normalizeArray(
            $values->filter(function (LocalizedFallbackValue $value) {
                return
                    ($value->getString() !== '' && $value->getString() !== null)
                    || ($value->getText() !== '' && $value->getText() !== null);
            })
        );
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
            return $this->normalizeLocalizedFallbackValue($value);
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
