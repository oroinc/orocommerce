<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;

class LoadCustomerDemoData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadAccountUserDemoData',
            'OroB2B\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerInternalRatingDemoData',
            'OroB2B\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerGroupDemoData',
            'OroB2B\Bundle\PaymentBundle\Migrations\Data\Demo\ORM\LoadPaymentTermDemoData'

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var AccountUser[] $accountUsers */
        $accountUsers = $manager->getRepository('OroB2BCustomerBundle:AccountUser')->findAll();
        $internalRatings =
            $manager->getRepository(ExtendHelper::buildEnumValueClassName(Customer::INTERNAL_RATING_CODE))->findAll();
        $customerGroupRepository = $manager->getRepository('OroB2BCustomerBundle:CustomerGroup');

        $rootCustomer = null;
        $firstLevelCustomer = null;

        $rootGroup = $customerGroupRepository->findOneBy(['name' => 'Root']);
        $firstLevelGroup = $customerGroupRepository->findOneBy(['name' => 'First']);
        $secondLevelGroup = $customerGroupRepository->findOneBy(['name' => 'Second']);

        $paymentTermRepository = $manager->getRepository('OroB2BPaymentBundle:PaymentTerm');
        $paymentTerms = $paymentTermRepository->findAll();

        // create customers
        foreach ($accountUsers as $index => $accountUser) {
            $customer = $accountUser->getCustomer();
            switch ($index % 3) {
                case 0:
                    $customer->setGroup($rootGroup);
                    $rootCustomer = $customer;
                    break;
                case 1:
                    $customer
                        ->setGroup($firstLevelGroup)
                        ->setParent($rootCustomer)
                        ->setPaymentTerm($paymentTerms[array_rand($paymentTerms)]);
                    $firstLevelCustomer = $customer;
                    break;
                case 2:
                    $customer
                        ->setGroup($secondLevelGroup)
                        ->setParent($firstLevelCustomer)
                        ->setPaymentTerm($paymentTerms[array_rand($paymentTerms)]);
                    break;
            }
            $customer->setInternalRating($internalRatings[array_rand($internalRatings)]);
        }

        $manager->flush();
    }
}
