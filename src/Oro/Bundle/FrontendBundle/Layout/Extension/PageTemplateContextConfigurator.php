<?php

namespace Oro\Bundle\FrontendBundle\Layout\Extension;

use Symfony\Component\OptionsResolver\Options;

use Oro\Bundle\FrontendBundle\DependencyInjection\OroFrontendExtension;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

class PageTemplateContextConfigurator implements ContextConfiguratorInterface
{
    /** @var ConfigManager */
    private $configManager;

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
            ->setDefaults(
                [
                    'page_template' => function (Options $options, $value) {

                        if (!$value) {
                            $pageTemplates = $this->configManager->get(
                                OroFrontendExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . 'page_templates'
                            );

                            $routeName = $options['route_name'];

                            if (isset($pageTemplates[$routeName]) && $pageTemplates[$routeName]) {
                                $value = $pageTemplates[$routeName];
                            }
                        }

                        return $value;
                    }
                ]
            )
            ->setAllowedTypes(['page_template' => ['string', 'null']]);
    }
}
