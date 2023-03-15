<?php

namespace Oro\Bundle\PromotionBundle\Model;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Promotion aware entity helper.
 */
class PromotionAwareEntityHelper
{
    public function __construct(protected ConfigManager $configProvider)
    {
    }

    public function isCouponAware(object|string $objectOrClass): bool
    {
        $className = is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;

        return $this->getPromotionAwareInfo($className, 'is_coupon_aware');
    }

    public function isPromotionAware(object|string $objectOrClass): bool
    {
        $className = is_object($objectOrClass) ? $objectOrClass::class : $objectOrClass;

        return $this->getPromotionAwareInfo($className, 'is_promotion_aware');
    }

    protected function getPromotionAwareInfo(string $className, string $optionName): bool
    {
        $className = ClassUtils::getRealClass($className);
        if ($this->configProvider->hasConfig($className)) {
            /** @var Config $promotionAwareInfo */
            $promotionAwareInfo = $this->configProvider->getEntityConfig('promotion', $className);
            $promotionValues = $promotionAwareInfo->getValues();
            if (isset($promotionValues[$optionName])) {
                return $promotionValues[$optionName];
            }
        }

        return false;
    }
}
