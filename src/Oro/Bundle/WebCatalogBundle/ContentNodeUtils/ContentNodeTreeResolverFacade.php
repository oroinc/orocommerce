<?php

namespace Oro\Bundle\WebCatalogBundle\ContentNodeUtils;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

class ContentNodeTreeResolverFacade implements ContentNodeTreeResolverInterface
{
    /**
     * @var ContentNodeTreeResolverInterface
     */
    private $defaultResolver;

    /**
     * @var ContentNodeTreeResolverInterface
     */
    private $cachedResolver;

    /**
     * @param ContentNodeTreeResolverInterface $defaultResolver
     * @param ContentNodeTreeResolverInterface $cachedResolver
     */
    public function __construct(
        ContentNodeTreeResolverInterface $defaultResolver,
        ContentNodeTreeResolverInterface $cachedResolver
    ) {

        $this->defaultResolver = $defaultResolver;
        $this->cachedResolver = $cachedResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ContentNode $node, Scope $scope)
    {
        return $this->cachedResolver->supports($node, $scope) || $this->defaultResolver->supports($node, $scope);
    }

    /**
     * {@inheritdoc}
     */
    public function getResolvedContentNode(ContentNode $node, Scope $scope)
    {
        if ($this->cachedResolver->supports($node, $scope)) {
            return $this->cachedResolver->getResolvedContentNode($node, $scope);
        }

        if ($this->defaultResolver->supports($node, $scope)) {
            return $this->defaultResolver->getResolvedContentNode($node, $scope);
        }

        return null;
    }
}
