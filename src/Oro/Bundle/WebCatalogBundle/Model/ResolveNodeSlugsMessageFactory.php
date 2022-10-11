<?php

namespace Oro\Bundle\WebCatalogBundle\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogResolveContentNodeSlugsTopic;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

/**
 * Encapsulates common methods for working with {@see WebCatalogResolveContentNodeSlugsTopic} MQ message.
 */
class ResolveNodeSlugsMessageFactory
{
    protected DoctrineHelper $doctrineHelper;

    protected ConfigManager $configManager;

    public function __construct(DoctrineHelper $doctrineHelper, ConfigManager $configManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
    }

    public function createMessage(ContentNode $contentNode): array
    {
        return [
            WebCatalogResolveContentNodeSlugsTopic::ID => $contentNode->getId(),
            WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => $this->isCreateRedirect($contentNode),
        ];
    }

    public function getEntityFromMessage(array $data): ?ContentNode
    {
        $repository = $this->doctrineHelper->getEntityRepositoryForClass(ContentNode::class);

        return $repository->find($data[WebCatalogResolveContentNodeSlugsTopic::ID]);
    }

    public function getCreateRedirectFromMessage(array $data): bool
    {
        return $data[WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT];
    }

    protected function isCreateRedirect(ContentNode $contentNode): bool
    {
        $redirectGenerationStrategy = $this->configManager->get('oro_redirect.redirect_generation_strategy');
        if ($redirectGenerationStrategy === Configuration::STRATEGY_ALWAYS) {
            return true;
        }

        if ($redirectGenerationStrategy === Configuration::STRATEGY_NEVER) {
            return false;
        }

        return $contentNode->getSlugPrototypesWithRedirect()->getCreateRedirect();
    }
}
