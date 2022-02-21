<?php

namespace Oro\Bundle\WebCatalogBundle\Cache;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\WebCatalogRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Psr\Cache\CacheItemPoolInterface;

/**
 * The cache for web catalog content node tree.
 */
class ContentNodeTreeCache
{
    private DoctrineHelper $doctrineHelper;
    private CacheItemPoolInterface $cache;

    public function __construct(DoctrineHelper $doctrineHelper, CacheItemPoolInterface $cache)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->cache = $cache;
    }

    /**
     * Gets a content node tree from the cache
     */
    public function fetch(int $nodeId, int $scopeId) : ResolvedContentNode|false|null
    {
        $cacheItem = $this->cache->getItem($this->getCacheKey($nodeId, $scopeId));
        if (!$cacheItem->isHit()) {
            return false;
        }
        if (empty($cacheItem->get())) {
            return null;
        }
        $cachedData = $cacheItem->get();
        $this->resolveReferences($cachedData);
        return $this->deserializeCachedNode($cachedData);
    }

    /**
     * Saves a content node tree to the cache
     */
    public function save(int $nodeId, int $scopeId, ?ResolvedContentNode $tree): void
    {
        $cacheItem = $this->cache->getItem($this->getCacheKey($nodeId, $scopeId));
        $cacheItem->set(null === $tree ? [] : $this->convertResolvedContentNode($tree));
        $this->cache->save($cacheItem);
    }

    /**
     * Deletes a content node tree from the cache
     */
    public function delete(int $nodeId, int $scopeId): void
    {
        $this->cache->deleteItem($this->getCacheKey($nodeId, $scopeId));
    }

    /**
     * Delete content node cache entries for every scope
     */
    public function deleteForNode(ContentNode $node) : void
    {
        $nodeId = $node->getId();
        $webCatalog = $node->getWebCatalog();

        /** @var WebCatalogRepository $webCatalogRepository */
        $webCatalogRepository = $this->doctrineHelper->getEntityRepositoryForClass(WebCatalog::class);
        $scopeIds = $webCatalogRepository->getUsedScopesIds($webCatalog);
        $cacheKeys = [];
        foreach ($scopeIds as $scopeId) {
            $cacheKeys[] = $this->getCacheKey($nodeId, $scopeId);
        }
        if (!empty($cacheKeys)) {
            $this->cache->deleteItems($cacheKeys);
        }
    }

    private function getCacheKey(int $nodeId, int $scopeId) : string
    {
        return sprintf('node_%s_scope_%s', $nodeId, $scopeId);
    }

    private function resolveReferences(array &$data): void
    {
        foreach ($data as &$value) {
            if (is_array($value)) {
                if (array_key_exists('entity_class', $value)) {
                    $value = $this->doctrineHelper->getEntityReference($value['entity_class'], $value['entity_id']);
                } else {
                    $this->resolveReferences($value);
                }
            }
        }
    }

    private function deserializeCachedNode(array $nodeData) : ResolvedContentNode
    {
        $resolvedVariant = new ResolvedContentVariant();
        $resolvedVariant->setData($nodeData['contentVariant']['data']);

        foreach ($nodeData['contentVariant']['localizedUrls'] as $localizedUrl) {
            $resolvedVariant->addLocalizedUrl($this->getLocalizedValue($localizedUrl));
        }

        $titles = new ArrayCollection();
        foreach ($nodeData['titles'] as $title) {
            $titles->add($this->getLocalizedValue($title));
        }

        $resolvedNode = new ResolvedContentNode(
            $nodeData['id'],
            $nodeData['identifier'],
            $titles,
            $resolvedVariant,
            $nodeData['resolveVariantTitle']
        );

        foreach ($nodeData['childNodes'] as $childNodeData) {
            $resolvedNode->addChildNode($this->deserializeCachedNode($childNodeData));
        }

        return $resolvedNode;
    }

    private function getLocalizedValue(array $localizedData) : LocalizedFallbackValue
    {
        $value = new LocalizedFallbackValue();
        $value->setString($localizedData['string']);
        $value->setLocalization($localizedData['localization']);
        $value->setFallback($localizedData['fallback']);

        return $value;
    }

    private function convertResolvedContentNode(ResolvedContentNode $resolvedNode): array
    {
        return [
            'id'                  => $resolvedNode->getId(),
            'identifier'          => $resolvedNode->getIdentifier(),
            'resolveVariantTitle' => $resolvedNode->isRewriteVariantTitle(),
            'titles'              => $this->convertLocalizedValuesArray($resolvedNode->getTitles()),
            'contentVariant'      => $this->convertResolvedContentVariant($resolvedNode->getResolvedContentVariant()),
            'childNodes'          => $this->convertArray($resolvedNode->getChildNodes())
        ];
    }

    private function convertLocalizedValue(LocalizedFallbackValue $value): array
    {
        return [
            'string'       => $value->getString() ?: $value->getText(),
            'localization' => $this->getEntityReference($value->getLocalization()),
            'fallback'     => $value->getFallback()
        ];
    }

    private function convertResolvedContentVariant(ResolvedContentVariant $resolvedVariant): array
    {
        return [
            'data'          => $this->convertArray($resolvedVariant->getData(), true),
            'localizedUrls' => $this->convertArray($resolvedVariant->getLocalizedUrls())
        ];
    }

    private function getEntityReference($object): ?array
    {
        if ($object === null) {
            return null;
        }

        return [
            'entity_class' => $this->doctrineHelper->getEntityClass($object),
            'entity_id'    => $this->doctrineHelper->getSingleEntityIdentifier($object)
        ];
    }

    private function convertLocalizedValuesArray(Collection $values): array
    {
        return $this->convertArray(
            $values->filter(function (LocalizedFallbackValue $value) {
                return
                    ($value->getString() !== '' && $value->getString() !== null)
                    || ($value->getText() !== '' && $value->getText() !== null);
            })
        );
    }

    private function convertArray(array|\Traversable $traversable, bool $skipNulls = false): array
    {
        $data = [];
        foreach ($traversable as $key => $value) {
            if ($skipNulls && $value === null) {
                continue;
            }

            if (is_object($value)) {
                $value = $this->convertObject($value);
            } elseif (is_array($value)) {
                $value = $this->convertArray($value);
            }

            $data[$key] = $value;
        }

        return $data;
    }

    private function convertObject($value): ?array
    {
        if ($value instanceof LocalizedFallbackValue) {
            return $this->convertLocalizedValue($value);
        }
        if ($value instanceof ResolvedContentNode) {
            return $this->convertResolvedContentNode($value);
        }
        if ($value instanceof \Traversable) {
            return $this->convertArray($value);
        }
        if ($this->doctrineHelper->isManageableEntity($value)) {
            return $this->getEntityReference($value);
        }

        return null;
    }
}
