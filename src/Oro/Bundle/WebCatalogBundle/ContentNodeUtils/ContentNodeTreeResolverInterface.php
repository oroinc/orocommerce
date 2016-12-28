<?php

namespace Oro\Bundle\WebCatalogBundle\ContentNodeUtils;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

interface ContentNodeTreeResolverInterface
{
    /**
     * @param ContentNode $node
     * @param Scope $scope
     * @return null|ResolvedContentNode
     */
    public function getResolvedContentNode(ContentNode $node, Scope $scope);

    /**
     * @param ContentNode $node
     * @param Scope $scope
     * @return bool
     */
    public function supports(ContentNode $node, Scope $scope);
}
