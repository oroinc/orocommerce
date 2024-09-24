<?php

namespace Oro\Bundle\WebCatalogBundle\ContentNodeDeletionChecker;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebCatalogBundle\Context\NotDeletableContentNodeResult;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\Configuration;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Checks on references in Config for Content Node
 */
class ContentNodeInConfigReferencesChecker implements ContentNodeDeletionCheckerInterface
{
    private const NAVIGATION_ROOT_KEY = Configuration::ROOT_NODE . '.' . Configuration::NAVIGATION_ROOT;

    private TranslatorInterface $translator;

    private ConfigManager $configManager;

    private WebsiteProviderInterface $websiteProvider;

    public function __construct(
        TranslatorInterface $translator,
        ConfigManager $configManager,
        WebsiteProviderInterface $websiteProvider
    ) {
        $this->translator = $translator;
        $this->configManager = $configManager;
        $this->websiteProvider = $websiteProvider;
    }

    #[\Override]
    public function check(ContentNode $contentNode): ?NotDeletableContentNodeResult
    {
        $result = new NotDeletableContentNodeResult();

        $configs = $this->configManager->getValues(
            self::NAVIGATION_ROOT_KEY,
            $this->websiteProvider->getWebsites(),
            false,
            true
        );

        $filteredConfigs = array_filter($configs, fn ($config) => $config['value'] == $contentNode->getId());

        if ($filteredConfigs) {
            $result->setWarningMessageParams([
                '%key%' => self::NAVIGATION_ROOT_KEY,
                '%subject%' => $this->translator->trans('oro.webcatalog.system_configuration.label')
            ]);

            return $result;
        }

        return null;
    }
}
