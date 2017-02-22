<?php

namespace Oro\Bundle\WebCatalogBundle\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

class ContentNodeTreeResolver implements ContentNodeTreeResolverInterface
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param Cache $cache
     */
    public function __construct(DoctrineHelper $doctrineHelper, Cache $cache)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->cache = $cache;
    }

    /**
     * @param ContentNode $node
     * @param Scope $scope
     * @return string
     */
    public static function getCacheKey(ContentNode $node, Scope $scope)
    {
        return sprintf('node_%s_scope_%s', $node->getId(), $scope->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function getResolvedContentNode(ContentNode $node, Scope $scope)
    {
        $cachedData = $this->cache->fetch(self::getCacheKey($node, $scope));
        if ($cachedData) {
            $this->resolveReferences($cachedData);

            return $this->deserializeCachedNode($cachedData);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ContentNode $node, Scope $scope)
    {
        return $this->cache->contains(self::getCacheKey($node, $scope));
    }

    /**
     * @param array $nodeData
     * @return ResolvedContentNode
     */
    private function deserializeCachedNode(array $nodeData)
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

    /**
     * @param array $data
     */
    private function resolveReferences(array &$data)
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

    /**
     * @param array $localizedData
     * @return LocalizedFallbackValue
     */
    private function getLocalizedValue(array $localizedData)
    {
        $value = new LocalizedFallbackValue();
        $value->setString($localizedData['string']);
        $value->setLocalization($localizedData['localization']);
        $value->setFallback($localizedData['fallback']);

        return $value;
    }
}
