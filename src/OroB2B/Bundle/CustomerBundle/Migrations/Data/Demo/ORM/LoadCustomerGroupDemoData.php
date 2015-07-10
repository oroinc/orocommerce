<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;

class LoadCustomerGroupDemoData extends AbstractFixture implements DependentFixtureInterface
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
            'OroB2B\Bundle\PaymentBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData'
        ];
    }

    private function getData()
    {
        return [
            'Root',
            'First',
            'Second'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $paymentTermRepository = $manager->getRepository('OroB2BPaymentBundle:PaymentTerm');
        $paymentTerms = $paymentTermRepository->findAll();

        foreach ($this->getData() as $groupName) {
            $customerGroup = new CustomerGroup();
            $customerGroup->setName($groupName);
            $customerGroup->setPaymentTerm($paymentTerms[array_rand($paymentTerms)]);
            $manager->persist($customerGroup);
        }

        $manager->flush();
    }
}
