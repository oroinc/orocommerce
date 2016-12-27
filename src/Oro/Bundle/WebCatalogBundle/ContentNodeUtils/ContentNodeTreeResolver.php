<?php

namespace Oro\Bundle\WebCatalogBundle\ContentNodeUtils;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;

class ContentNodeTreeResolver implements ContentNodeTreeResolverInterface
{
    const ROOT_NODE_IDENTIFIER = 'root';
    const IDENTIFIER_GLUE = '__';

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ScopeMatcher $scopeMatcher
     */
    public function __construct(DoctrineHelper $doctrineHelper, ScopeMatcher $scopeMatcher)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->scopeMatcher = $scopeMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getResolvedContentNode(ContentNode $node, Scope $scope)
    {
        return $this->getResolvedTree($node, $scope);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ContentNode $node, Scope $scope)
    {
        return true;
    }

    /**
     * @param ContentNode $node
     * @param Scope $scope
     * @return null|ResolvedContentNode
     */
    protected function getResolvedTree(ContentNode $node, Scope $scope)
    {
        if (false === $this->scopeMatcher->getMatchingScopePriority($node->getScopesConsideringParent(), $scope)) {
            return null;
        }

        $identifier = $this->getIdentifier($node);
        $resolvedContentVariant = $this->getResolvedContentVariant($node->getContentVariants(), $scope);
        if (!$resolvedContentVariant) {
            return null;
        }

        $resolvedNode = new ResolvedContentNode(
            $node->getId(),
            $identifier,
            $node->getTitles(),
            $resolvedContentVariant
        );

        foreach ($node->getChildNodes() as $childNode) {
            $resolvedChildNode = $this->getResolvedTree($childNode, $scope);
            if ($resolvedChildNode) {
                $resolvedNode->addChildNode($resolvedChildNode);
            }
        }

        return $resolvedNode;
    }

    /**
     * @param Collection|ContentVariant[] $contentVariants
     * @param Scope $scope
     * @return ResolvedContentVariant|null
     */
    protected function getResolvedContentVariant(Collection $contentVariants, Scope $scope)
    {
        /** @var ContentVariant $filteredVariant */
        $filteredVariant = $this->scopeMatcher->getBestMatchByScope($contentVariants, $scope);
        if (!$filteredVariant) {
            return null;
        }

        $resolvedVariant = new ResolvedContentVariant();
        $metadata = $this->doctrineHelper->getEntityMetadata($filteredVariant);
        foreach ($metadata->getFieldNames() as $fieldName) {
            $resolvedVariant->{$fieldName} = $metadata->getFieldValue($filteredVariant, $fieldName);
        }
        /**
         * @var string $associationName
         * @var $associationMapping
         */
        foreach ($metadata->getAssociationNames() as $associationName) {
            $associatedValue = $metadata->getFieldValue($filteredVariant, $associationName);

            if ($associationName === 'slugs') {
                $this->fillSlugs($associatedValue, $resolvedVariant);
            }
            if ($associatedValue instanceof Collection || $associatedValue instanceof ContentNode) {
                continue;
            }
            if ($associatedValue) {
                $resolvedVariant->{$associationName} = $associatedValue;
            }
        }

        return $resolvedVariant;
    }

    /**
     * @param ContentNode $node
     * @return string
     */
    protected function getIdentifier(ContentNode $node)
    {
        $defaultVariant = $node->getDefaultVariant();
        /** @var Slug $slug */
        $slug = $defaultVariant->getSlugs()
            ->filter(
                function (Slug $slug) {
                    return $slug->getLocalization() === null;
                }
            )
            ->first();

        if (!$slug) {
            $slug = $defaultVariant->getSlugs()->first();
        }

        $url = trim($slug->getUrl(), '/');
        $identifierParts = [self::ROOT_NODE_IDENTIFIER];
        if ($url) {
            if (strpos($url, '/') > 0) {
                $identifierParts = array_merge($identifierParts, explode('/', $url));
            } else {
                $identifierParts[] = $url;
            }
        }

        return implode(self::IDENTIFIER_GLUE, $identifierParts);
    }

    /**
     * @param Collection|Slug[] $slugs
     * @param ResolvedContentVariant $resolvedVariant
     */
    protected function fillSlugs(Collection $slugs, ResolvedContentVariant $resolvedVariant)
    {
        foreach ($slugs as $slug) {
            $localizedUrl = new LocalizedFallbackValue();
            $localizedUrl->setString($slug->getUrl());
            $localizedUrl->setLocalization($slug->getLocalization());

            $resolvedVariant->addLocalizedUrl($localizedUrl);
        }
    }
}
