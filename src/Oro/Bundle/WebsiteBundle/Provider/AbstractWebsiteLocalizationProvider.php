<?php

namespace Oro\Bundle\WebsiteBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;

abstract class AbstractWebsiteLocalizationProvider
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var LocalizationManager */
    protected $localizationManager;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var WebsiteRepository */
    private $websiteRepository;

    /**
     * @param ConfigManager $configManager
     * @param LocalizationManager $localizationManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ConfigManager $configManager,
        LocalizationManager $localizationManager,
        DoctrineHelper $doctrineHelper
    ) {
        $this->configManager = $configManager;
        $this->localizationManager = $localizationManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @return WebsiteRepository
     */
    protected function getWebsiteRepository()
    {
        if (!$this->websiteRepository) {
            $this->websiteRepository = $this->doctrineHelper->getEntityRepositoryForClass(Website::class);
        }

        return $this->websiteRepository;
    }

    /**
     * @return array
     */
    protected function getEnabledLocalizationIds()
    {
        return $this->configManager->get(Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS));
    }

    /**
     * @param Website $website
     * @return Localization[]
     */
    abstract public function getLocalizations(Website $website);

    /**
     * @param int $websiteId
     * @return Localization[]
     */
    public function getLocalizationsByWebsiteId($websiteId = null)
    {
        $website = null;
        if (null !== $websiteId && is_int($websiteId)) {
            $website = $this->getWebsiteRepository()->findOneBy([
                'id' => $websiteId,
            ]);
        }

        if (!$website) {
            $website = $this->getWebsiteRepository()->getDefaultWebsite();
        }

        return $this->getLocalizations($website);
    }
}
