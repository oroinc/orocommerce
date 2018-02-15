<?php

namespace Oro\Bundle\WebCatalogBundle\ContentNodeUtils;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\Dumper\ContentNodeTreeDumper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

class ContentNodeTreeResolverFacade implements ContentNodeTreeResolverInterface
{
    /**
     * @deprecated since version 2.5, to be removed in 2.7.
     * @var ContentNodeTreeResolverInterface
     */
    private $defaultResolver;

    /**
     * @var ContentNodeTreeResolverInterface
     */
    private $cachedResolver;

    /**
     * @var ContentNodeTreeDumper
     */
    private $contentNodeTreeDumper;

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
     * @param ContentNodeTreeDumper $contentNodeTreeDumper
     */
    public function setContentNodeTreeDumper(ContentNodeTreeDumper $contentNodeTreeDumper)
    {
        $this->contentNodeTreeDumper = $contentNodeTreeDumper;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ContentNode $node, Scope $scope)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getResolvedContentNode(ContentNode $node, Scope $scope)
    {
        if (!$this->cachedResolver->supports($node, $scope)) {
            $this->contentNodeTreeDumper->dump($node, $scope);
        }

        return $this->cachedResolver->getResolvedContentNode($node, $scope);
    }
}
