<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntitiesEvent;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;

class RestrictIndexProductsEventListener
{
    /** @var ProductVisibilityQueryBuilderModifier */
    protected $modifier;

    /** @var string */
    protected $configPath;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ProductVisibilityQueryBuilderModifier $modifier
     * @param ConfigManager $configManager
     */
    public function __construct(ProductVisibilityQueryBuilderModifier $modifier, ConfigManager $configManager)
    {
        $this->modifier = $modifier;
        $this->configManager = $configManager;
    }

    /**
     * @param string $configurationPath
     * @return $this
     */
    public function setSystemConfigurationPath($configurationPath)
    {
        $this->configPath = $configurationPath;

        return $this;
    }

    /**
     * @param RestrictIndexEntitiesEvent $event
     */
    public function onRestrictIndexEntitiesEvent(RestrictIndexEntitiesEvent $event)
    {
        if ($event->getEntityClass() == Product::class) {
            if (!$this->configPath) {
                throw
                new \LogicException('SystemConfigurationPath not configured for RestrictIndexProductsEventListener');
            }

            $this->modifier->modifyByStatus($event->getQueryBuilder(), [Product::STATUS_ENABLED]);
            $inventoryStatuses = $this->configManager->get($this->configPath);
            $this->modifier->modifyByInventoryStatus($event->getQueryBuilder(), $inventoryStatuses);
        }
    }
}
