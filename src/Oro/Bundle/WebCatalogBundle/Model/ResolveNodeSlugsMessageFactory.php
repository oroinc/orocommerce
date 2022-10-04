<?php

namespace Oro\Bundle\WebCatalogBundle\Model;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\WebCatalogBundle\Async\Topic\WebCatalogResolveContentNodeSlugsTopic;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Encapsulates common methods for working with {@see WebCatalogResolveContentNodeSlugsTopic} MQ message.
 */
class ResolveNodeSlugsMessageFactory
{
    const ID = 'id';
    const CREATE_REDIRECT = 'createRedirect';

    /**
     * @var OptionsResolver
     */
    protected $resolver;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function __construct(DoctrineHelper $doctrineHelper, ConfigManager $configManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
    }

    /**
     * @param ContentNode $contentNode
     * @return array
     */
    public function createMessage(ContentNode $contentNode)
    {
        return [
            WebCatalogResolveContentNodeSlugsTopic::ID => $contentNode->getId(),
            WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT => $this->isCreateRedirect($contentNode),
        ];
    }

    /**
     * @param array $data
     * @return ContentNode
     */
    public function getEntityFromMessage($data)
    {
        $repository = $this->doctrineHelper->getEntityRepositoryForClass(ContentNode::class);

        return $repository->find($data[WebCatalogResolveContentNodeSlugsTopic::ID]);
    }

    /**
     * @param array $data
     * @return bool
     */
    public function getCreateRedirectFromMessage($data)
    {
        return $data[WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT];
    }

    /**
     * @return OptionsResolver
     */
    protected function getOptionsResolver()
    {
        if (null === $this->resolver) {
            $resolver = new OptionsResolver();
            $resolver->setRequired([
                WebCatalogResolveContentNodeSlugsTopic::ID,
                WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT
            ]);

            $resolver->setAllowedTypes(WebCatalogResolveContentNodeSlugsTopic::ID, 'int');
            $resolver->setAllowedTypes(WebCatalogResolveContentNodeSlugsTopic::CREATE_REDIRECT, 'bool');

            $this->resolver = $resolver;
        }

        return $this->resolver;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getResolvedData($data)
    {
        try {
            return $this->getOptionsResolver()->resolve($data);
        } catch (\Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }
    }

    /**
     * @param ContentNode $contentNode
     * @return bool
     */
    protected function isCreateRedirect(ContentNode $contentNode)
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
