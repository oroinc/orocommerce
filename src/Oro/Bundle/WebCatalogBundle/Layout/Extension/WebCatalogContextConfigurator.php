<?php

namespace Oro\Bundle\WebCatalogBundle\Layout\Extension;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextConfiguratorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class WebCatalogContextConfigurator implements ContextConfiguratorInterface
{
    const CONTEXT_VARIABLE = 'web_catalog_id';

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
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $context->getResolver()
            ->setRequired([WebCatalogContextConfigurator::CONTEXT_VARIABLE])
            ->setAllowedTypes([WebCatalogContextConfigurator::CONTEXT_VARIABLE => ['null', 'string', 'integer']]);

        $context->set(
            WebCatalogContextConfigurator::CONTEXT_VARIABLE,
            $this->configManager->get('oro_web_catalog.web_catalog')
        );
    }
}
