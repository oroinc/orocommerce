<?php

namespace Oro\Bundle\WebCatalogBundle\Cache\Dumper;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeResolver as CacheContentNodeTreeResolver;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

class ContentNodeTreeDumper
{
    /**
     * @var ContentNodeTreeResolverInterface
     */
    private $nodeTreeResolver;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param ContentNodeTreeResolverInterface $nodeTreeResolver
     * @param DoctrineHelper $doctrineHelper
     * @param Cache $cache
     */
    public function __construct(
        ContentNodeTreeResolverInterface $nodeTreeResolver,
        DoctrineHelper $doctrineHelper,
        Cache $cache
    ) {
        $this->nodeTreeResolver = $nodeTreeResolver;
        $this->doctrineHelper = $doctrineHelper;
        $this->cache = $cache;
    }

    /**
     * @param ContentNode $node
     * @param Scope $scope
     */
    public function dump(ContentNode $node, Scope $scope)
    {
        $resolvedNode = $this->nodeTreeResolver->getResolvedContentNode($node, $scope);
        $convertedData = [];
        if ($resolvedNode) {
            $convertedData = $this->convertResolvedContentNode($resolvedNode);
        }
        $this->saveCache($node, $scope, $convertedData);
    }

    /**
     * @param ContentNode $node
     * @param Scope $scope
     * @param array $convertedData
     */
    private function saveCache(ContentNode $node, Scope $scope, array $convertedData)
    {
        $this->cache->save(CacheContentNodeTreeResolver::getCacheKey($node, $scope), $convertedData);
    }

    /**
     * @param ResolvedContentNode $resolvedNode
     * @return array
     */
    private function convertResolvedContentNode(ResolvedContentNode $resolvedNode)
    {
        return [
            'id' => $resolvedNode->getId(),
            'identifier' => $resolvedNode->getIdentifier(),
            'titles' => $this->convertLocalizedValuesArray($resolvedNode->getTitles()),
            'contentVariant' => $this->convertResolvedContentVariant($resolvedNode->getResolvedContentVariant()),
            'childNodes' => $this->convertArray($resolvedNode->getChildNodes())
        ];
    }

    /**
     * @param LocalizedFallbackValue $value
     * @return array
     */
    private function convertLocalizedValue(LocalizedFallbackValue $value)
    {
        return [
            'string' => $value->getString() ?: $value->getText(),
            'localization' => $this->getEntityReference($value->getLocalization()),
            'fallback' => $value->getFallback()
        ];
    }

    /**
     * @param ResolvedContentVariant $resolvedContentVariant
     * @return array
     */
    private function convertResolvedContentVariant(ResolvedContentVariant $resolvedContentVariant)
    {
        return [
            'data' => $this->convertArray($resolvedContentVariant->getData(), true),
            'localizedUrls' => $this->convertArray($resolvedContentVariant->getLocalizedUrls())
        ];
    }

    /**
     * @param object $object
     * @return array|null
     */
    private function getEntityReference($object)
    {
        if ($object === null) {
            return null;
        }

        return [
            'entity_class' => $this->doctrineHelper->getEntityClass($object),
            'entity_id' => $this->doctrineHelper->getSingleEntityIdentifier($object)
        ];
    }

    /**
     * @param Collection $values
     * @return string
     */
    private function convertLocalizedValuesArray(Collection $values)
    {
        return $this->convertArray(
            $values->filter(
                function (LocalizedFallbackValue $value) {
                    return ($value->getString() !== '' && $value->getString() !== null)
                        || ($value->getText() !== '' && $value->getText() !== null);
                }
            )
        );
    }

    /**
     * @param array|\Traversable $traversable
     * @param bool $skipNulls
     * @return array|null
     */
    private function convertArray($traversable, $skipNulls = false)
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

    /**
     * @param object $value
     * @return array|null
     */
    private function convertObject($value)
    {
        if ($value instanceof LocalizedFallbackValue) {
            return $this->convertLocalizedValue($value);
        } elseif ($value instanceof ResolvedContentNode) {
            return $this->convertResolvedContentNode($value);
        } elseif ($value instanceof \Traversable) {
            return $this->convertArray($value);
        } elseif ($this->doctrineHelper->isManageableEntity($value)) {
            return $this->getEntityReference($value);
        }

        return null;
    }
}
