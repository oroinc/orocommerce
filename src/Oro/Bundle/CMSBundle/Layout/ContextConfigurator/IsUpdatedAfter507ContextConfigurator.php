<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Layout\ContextConfigurator;

use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

/**
 * Component added back for theme layout BC from version 5.0
 *
 * Aims to fix backwards incompatible changes to the layout of CMS landing page
 * that was introduced in 5.0.7 release and broke the customizations.
 *
 * if `is_updated_after_507` is true then will be used new configuration and templates, otherwise legacy will be used
 */
class IsUpdatedAfter507ContextConfigurator implements ContextConfiguratorInterface
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context): void
    {
        $context->getResolver()
            ->setRequired([Configuration::IS_UPDATED_AFTER_507])
            ->setAllowedTypes(Configuration::IS_UPDATED_AFTER_507, ['boolean']);

        $context->set(
            Configuration::IS_UPDATED_AFTER_507,
            (bool) $this->configManager->get(Configuration::getConfigKeyByName(Configuration::IS_UPDATED_AFTER_507))
        );
    }
}
