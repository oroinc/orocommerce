<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Fixture\AbstractEntityReferenceFixture;
use Oro\Bundle\CustomerBundle\Entity\Customer;

class LoadCustomerDemoData extends AbstractEntityReferenceFixture implements DependentFixtureInterface
{
    const ACCOUNT_REFERENCE_PREFIX = 'customer_demo_data';

    /** @var array */
    protected $customers = [
        'Company A' => [
            'group' => 'All Customers',
            'subsidiaries' => [
                'Company A - East Division' => [
                    'group' => 'All Customers',
                ],
                'Company A - West Division' => [
                    'group' => 'All Customers',
                ],
            ],
        ],
        'Wholesaler B' => [
            'group' => 'Wholesale Customers',
        ],
        'Partner C' => [
            'group' => 'Partners',
        ],
        'Customer G' => [
            'group' => 'All Customers',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCustomerInternalRatingDemoData::class,
            LoadCustomerGroupDemoData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $internalRatings = $this->getObjectReferencesByIds(
            $manager,
            ExtendHelper::buildEnumValueClassName(Customer::INTERNAL_RATING_CODE),
            LoadCustomerInternalRatingDemoData::getDataKeys()
        );

        /** @var \Oro\Bundle\UserBundle\Entity\User $customerOwner */
        $customerOwner = $manager->getRepository('OroUserBundle:User')->findOneBy([]);

        foreach ($this->customers as $customerName => $customerData) {
            /** @var CustomerGroup $customerGroup */
            $customerGroup = $this->getReference(
                LoadCustomerGroupDemoData::ACCOUNT_GROUP_REFERENCE_PREFIX . $customerData['group']
            );

            $customer = new Customer();
            $customer
                ->setName($customerName)
                ->setGroup($customerGroup)
                ->setParent(null)
                ->setOrganization($customerOwner->getOrganization())
                ->setOwner($customerOwner)
                ->setInternalRating($internalRatings[array_rand($internalRatings)]);

            $manager->persist($customer);
            $this->addReference(static::ACCOUNT_REFERENCE_PREFIX . $customer->getName(), $customer);

            if (isset($customerData['subsidiaries'])) {
                foreach ($customerData['subsidiaries'] as $subsidiaryName => $subsidiaryData) {
                    /** @var CustomerGroup $subsidiaryGroup */
                    $subsidiaryGroup = $this->getReference(
                        LoadCustomerGroupDemoData::ACCOUNT_GROUP_REFERENCE_PREFIX . $subsidiaryData['group']
                    );
                    $subsidiary = new Customer();
                    $subsidiary
                        ->setName($subsidiaryName)
                        ->setGroup($subsidiaryGroup)
                        ->setParent($customer)
                        ->setOrganization($customerOwner->getOrganization())
                        ->setOwner($customerOwner)
                        ->setInternalRating($internalRatings[array_rand($internalRatings)]);

                    $manager->persist($subsidiary);
                    $this->addReference(static::ACCOUNT_REFERENCE_PREFIX . $subsidiary->getName(), $subsidiary);
                }
            }
        }

        $manager->flush();
    }
}
