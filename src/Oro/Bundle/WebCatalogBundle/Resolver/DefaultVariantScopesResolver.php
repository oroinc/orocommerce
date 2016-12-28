<?php

namespace Oro\Bundle\WebCatalogBundle\Resolver;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;

class DefaultVariantScopesResolver
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param ContentNode $contentNode
     */
    public function resolve(ContentNode $contentNode)
    {
        /** @var ContentNodeRepository $contentNodeRepository */
        $contentNodeRepository = $this->registry
            ->getManagerForClass(ContentNode::class)
            ->getRepository(ContentNode::class);
        $this->updateDefaultVariantScopesWithDepended($contentNode, $contentNodeRepository);
    }

    /**
     * @param ContentNode $contentNode
     * @param ContentNodeRepository $contentNodeRepository
     */
    protected function updateDefaultVariantScopesWithDepended(
        ContentNode $contentNode,
        ContentNodeRepository $contentNodeRepository
    ) {
        $contentNodesWithParentFallbackUsed = $contentNodeRepository->getDirectNodesWithParentScopeUsed($contentNode);

        $this->updateDefaultVariantScopes($contentNode);

        foreach ($contentNodesWithParentFallbackUsed as $node) {
            $this->updateDefaultVariantScopesWithDepended($node, $contentNodeRepository);
        }
    }

    /**
     * @param ContentNode $contentNode
     */
    protected function updateDefaultVariantScopes(ContentNode $contentNode)
    {
        $defaultVariant = $contentNode->getDefaultVariant();

        if ($defaultVariant) {
            $defaultVariant->resetScopes();

            foreach ($this->getDefaultVariantScopes($contentNode) as $scope) {
                $defaultVariant->addScope($scope);
            }
        }
    }

    /**
     * @param ContentNode $contentNode
     * @return \Generator
     */
    protected function getDefaultVariantScopes(ContentNode $contentNode)
    {
        $contentNodeScopes = $contentNode->getScopesConsideringParent();

        $contentVariantsScopes = [];
        foreach ($contentNode->getContentVariants() as $contentVariant) {
            $contentVariantsScopes = array_merge($contentVariantsScopes, $contentVariant->getScopes()->toArray());
        }

        foreach ($contentNodeScopes as $nodeScope) {
            if (!in_array($nodeScope, $contentVariantsScopes, true)) {
                yield $nodeScope;
            }
        }
    }
}
