<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class LoadScopeCustomerDemoData extends AbstractFixture implements FixtureInterface, DependentFixtureInterface
{
    const SCOPE_ACCOUNT_REFERENCE_PREFIX = 'scope_customer_demo_data';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCustomerDemoData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Customer $customer */
        $customers = $manager->getRepository('OroCustomerBundle:Customer')->findAll();
        foreach ($customers as $customer) {
            $scope = new Scope();
            $scope->setCustomer($customer);
            $this->addReference(static::SCOPE_ACCOUNT_REFERENCE_PREFIX . $customer->getName(), $scope);
            $manager->persist($scope);
        }

        $manager->flush();
    }
}
