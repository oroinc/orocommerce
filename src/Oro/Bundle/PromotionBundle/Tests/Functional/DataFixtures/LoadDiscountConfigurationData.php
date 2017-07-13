<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;

class LoadDiscountConfigurationData extends AbstractFixture
{
    const DISCOUNT_CONFIGURATION_ORDER_PERCENT = 'discount_configuration_order_percent';
    const DISCOUNT_CONFIGURATION_ORDER_AMOUNT = 'discount_configuration_order_amount';

    /** @var array */
    protected static $configurations = [
        self::DISCOUNT_CONFIGURATION_ORDER_PERCENT => [
            'type' => 'order',
            'options' => [
                AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
                AbstractDiscount::DISCOUNT_VALUE => 0.1,
            ],
        ],
        self::DISCOUNT_CONFIGURATION_ORDER_AMOUNT => [
            'type' => 'order',
            'options' => [
                AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                AbstractDiscount::DISCOUNT_VALUE => 10,
                AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
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
