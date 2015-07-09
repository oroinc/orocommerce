<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;

class LoadGroups extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on
     *
     * @return array
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTermData'
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createGroup(
            $manager,
            'customer_group.group1',
            $this->getReference(LoadPaymentTermData::TERM_LABEL_NET_10)
        );
        $this->createGroup(
            $manager,
            'customer_group.group2',
            $this->getReference(LoadPaymentTermData::TERM_LABEL_NET_20)
        );
        $this->createGroup(
            $manager,
            'customer_group.group3',
            $this->getReference(LoadPaymentTermData::TERM_LABEL_NET_30)
        );

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @return CustomerGroup
     */
    protected function createGroup(ObjectManager $manager, $name, PaymentTerm $paymentTerm)
    {
        $group = new CustomerGroup();
        $group->setName($name);
        $group->setPaymentTerm($paymentTerm);
        $manager->persist($group);
        $this->addReference($name, $group);

        return $group;
    }
}
