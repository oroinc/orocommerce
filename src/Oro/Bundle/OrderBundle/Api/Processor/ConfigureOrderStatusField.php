<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\OrderBundle\Provider\OrderConfigurationProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Prevents getting and setting order status value
 * when "Enable External Status Management" configuration option is disabled.
 */
class ConfigureOrderStatusField implements ProcessorInterface
{
    private OrderConfigurationProviderInterface $configurationProvider;

    public function __construct(OrderConfigurationProviderInterface $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        if ($this->configurationProvider->isExternalStatusManagementEnabled()) {
            return;
        }

        $statusField = $context->getResult()->findField('status', true);
        if (null === $statusField || $statusField->isExcluded()) {
            return;
        }

        $statusField->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        switch ($context->getTargetAction()) {
            case ApiAction::CREATE:
            case ApiAction::UPDATE:
            case ApiAction::UPDATE_RELATIONSHIP:
                $statusFieldFormOptions = $statusField->getFormOptions();
                if (null === $statusFieldFormOptions || !\array_key_exists('mapped', $statusFieldFormOptions)) {
                    $statusFieldFormOptions['mapped'] = false;
                    $statusField->setFormOptions($statusFieldFormOptions);
                }
                break;
        }
    }
}
