<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Listener prevents access to open orders grid with disabled configuration option.
 */
class OpenOrdersDatagridListener
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function onPreBuild(PreBuild $event)
    {
        if (!$this->configManager->get('oro_checkout.frontend_show_open_orders')) {
            throw new NotFoundHttpException();
        }
    }
}
