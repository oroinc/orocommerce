<?php

namespace Oro\Bundle\WebCatalogBundle\Resolver;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Provider\ScopeWebCatalogProvider;

/**
 * Calculate and set scopes for default content variant based on node restrictions and non-default variants restrictions
 */
class DefaultVariantScopesResolver
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @param ManagerRegistry $registry
     * @param ScopeManager $scopeManager
     */
    public function __construct(ManagerRegistry $registry, ScopeManager $scopeManager)
    {
        $this->registry = $registry;
        $this->scopeManager = $scopeManager;
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

        $this->removeEmptyScopeFromVariants($contentNode);
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
            $contentVariantsScopes[] = $contentVariant->getScopes()->toArray();
        }

        if ($contentVariantsScopes) {
            $contentVariantsScopes = array_merge(...$contentVariantsScopes);
        }

        foreach ($contentNodeScopes as $nodeScope) {
            if (!in_array($nodeScope, $contentVariantsScopes, true)) {
                yield $nodeScope;
            }
        }
    }

    /**
     * @param ContentNode $contentNode
     */
    protected function removeEmptyScopeFromVariants(ContentNode $contentNode)
    {
        $defaultScope = $this->scopeManager->findOrCreate(
            'web_content',
            [ScopeWebCatalogProvider::WEB_CATALOG => $contentNode->getWebCatalog()]
        );
        foreach ($contentNode->getContentVariants() as $contentVariant) {
            if ($contentVariant->getScopes()->contains($defaultScope)) {
                $contentVariant->getScopes()->removeElement($defaultScope);
            }
        }
    }
}
