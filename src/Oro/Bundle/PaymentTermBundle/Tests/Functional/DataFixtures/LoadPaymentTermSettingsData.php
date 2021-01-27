<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;

class LoadPaymentTermSettingsData extends AbstractFixture implements FixtureInterface
{
    /**
     * @var array Transports configuration
     */
    public static $transportData = [
        [
            'reference' => 'payment_term:transport_1',
        ],
        [
            'reference' => 'payment_term:transport_2',
        ],
        [
            'reference' => 'payment_term:transport_3',
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$transportData as $data) {
            $entity = new PaymentTermSettings();
            $manager->persist($entity);
            $this->setReference($data['reference'], $entity);
        }
        $manager->flush();
    }
}
