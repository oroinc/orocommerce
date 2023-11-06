<?php

namespace Oro\Bundle\PromotionBundle\Model;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * The utility class that helps to check if an entity is a coupon or promotion aware.
 */
class PromotionAwareEntityHelper
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function isCouponAware(object|string $objectOrClass): bool
    {
        return $this->isOptionEnabled($objectOrClass, 'is_coupon_aware');
    }

    public function isPromotionAware(object|string $objectOrClass): bool
    {
        return $this->isOptionEnabled($objectOrClass, 'is_promotion_aware');
    }

    private function isOptionEnabled(object|string $objectOrClass, string $optionName): bool
    {
        $entityClass = \is_object($objectOrClass) ? ClassUtils::getClass($objectOrClass) : $objectOrClass;
        if (!$this->configManager->hasConfig($entityClass)) {
            return false;
        }

        return $this->configManager->getEntityConfig('promotion', $entityClass)->is($optionName);
    }
}
