<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;

class LoadDiscountConfigurationData extends AbstractFixture
{
    const DISCOUNT_CONFIGURATION_ORDER_PERCENT = 'discount_configuration_order_percent';

    /** @var array */
    protected static $configurations = [
        self::DISCOUNT_CONFIGURATION_ORDER_PERCENT => [
            'type' => 'order',
            'options' => [
                AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
                AbstractDiscount::DISCOUNT_VALUE => 100,
            ],
        ],
    ];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach (static::$configurations as $reference => $configurationData) {
            $discountConfiguration = new DiscountConfiguration();

            $discountConfiguration->setType($configurationData['type']);
            $discountConfiguration->setOptions($configurationData['options']);

            $manager->persist($discountConfiguration);
            $this->setReference($reference, $discountConfiguration);
        }
        $manager->flush();
    }
}
