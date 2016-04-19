<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout;

class LoadAlternativeCheckouts extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'name' => [],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $name => $checkoutData) {
            $checkout = new AlternativeCheckout();
            $manager->persist($checkout);
            $this->setReference($name, $checkout);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderAddressData',
        ];
    }
}
