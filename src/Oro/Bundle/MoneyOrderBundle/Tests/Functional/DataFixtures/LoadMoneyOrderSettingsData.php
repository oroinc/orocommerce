<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;

class LoadMoneyOrderSettingsData extends AbstractFixture implements FixtureInterface
{
    const MONEY_ORDER_LABEL = 'Check/Money Order';
    const MONEY_ORDER_PAY_TO_VALUE = 'Johnson Brothers LLC.';
    const MONEY_ORDER_SEND_TO_VALUE = '1234 Main St. Smallville, CA 90048';
    /**
     * @var array Transports configuration
     */
    public static $transportData = [
        [
            'reference' => 'money_order:transport_1',
            'label' => self::MONEY_ORDER_LABEL,
            'short_label' => self::MONEY_ORDER_LABEL,
            'payTo' => self::MONEY_ORDER_PAY_TO_VALUE,
            'sendTo' => self::MONEY_ORDER_SEND_TO_VALUE,
        ],
        [
            'reference' => 'money_order:transport_2',
            'label' => self::MONEY_ORDER_LABEL,
            'short_label' => self::MONEY_ORDER_LABEL,
            'payTo' => self::MONEY_ORDER_PAY_TO_VALUE,
            'sendTo' => self::MONEY_ORDER_SEND_TO_VALUE,
        ],
        [
            'reference' => 'money_order:transport_3',
            'label' => self::MONEY_ORDER_LABEL,
            'short_label' => self::MONEY_ORDER_LABEL,
            'payTo' => self::MONEY_ORDER_PAY_TO_VALUE,
            'sendTo' => self::MONEY_ORDER_SEND_TO_VALUE,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$transportData as $data) {
            $entity = new MoneyOrderSettings();
            $entity->addLabel($this->createLocalizedValue($data['label']));
            $entity->addShortLabel($this->createLocalizedValue($data['short_label']));
            $entity->setPayTo($data['payTo']);
            $entity->setSendTo($data['sendTo']);
            $manager->persist($entity);
            $this->setReference($data['reference'], $entity);
        }
        $manager->flush();
    }

    /**
     * @param $string
     * @return LocalizedFallbackValue
     */
    protected function createLocalizedValue($string)
    {
        return (new LocalizedFallbackValue())->setString($string);
    }
}
