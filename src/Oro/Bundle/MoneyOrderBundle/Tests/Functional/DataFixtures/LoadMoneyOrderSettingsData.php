<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;

class LoadMoneyOrderSettingsData extends AbstractFixture implements FixtureInterface
{
    /**
     * @var array Transports configuration
     */
    public static $transportData = [
        [
            'reference' => 'money_order:transport_1',
            'label' => 'label_1',
            'short_label' => 'short_label_1',
            'payTo' => 'payTo_1',
            'sendTo' => 'sendTo_1',
        ],
        [
            'reference' => 'money_order:transport_2',
            'label' => 'label_2',
            'short_label' => 'short_label_2',
            'payTo' => 'payTo_2',
            'sendTo' => 'sendTo_2',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$transportData as $data) {
            $label = new LocalizedFallbackValue();
            $label->setString($data['label']);
            $short_label = new LocalizedFallbackValue();
            $short_label->setString($data['short_label']);

            $entity = new MoneyOrderSettings();
            $entity->addLabel($label);
            $entity->addShortLabel($short_label);
            $entity->setPayTo($data['payTo']);
            $entity->setSendTo($data['sendTo']);
            $manager->persist($entity);
            $this->setReference($data['reference'], $entity);
        }
        $manager->flush();
    }
}
