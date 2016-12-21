<?php

namespace Oro\Bundle\WebCatalogBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ScopeBundle\Manager\AbstractScopeCriteriaProvider;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

class ScopeWebCatalogProvider extends AbstractScopeCriteriaProvider
{
    const WEB_CATALOG = 'webCatalog';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return array
     */
    public function getCriteriaForCurrentScope()
    {
        return [self::WEB_CATALOG => $this->configManager->get('oro_web_catalog.web_catalog')];
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteriaField()
    {
        return static::WEB_CATALOG;
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteriaValueType()
    {
        return WebCatalog::class;
    }
}
