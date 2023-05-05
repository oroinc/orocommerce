<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

class LoadCustomerTaxCodes extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    public const REFERENCE_PREFIX = 'customer_tax_code';

    public const TAX_1 = 'TAX1';
    public const TAX_2 = 'TAX2';
    public const TAX_3 = 'TAX3';
    public const TAX_4 = 'TAX4';

    private const DATA = [
        self::TAX_1 => [
            'description'    => 'Tax description 1',
            'customers'      => [LoadCustomers::DEFAULT_ACCOUNT_NAME],
            'customerGroups' => []
        ],
        self::TAX_2 => [
            'description'    => 'Tax description 2',
            'customers'      => [],
            'customerGroups' => [LoadGroups::GROUP2]
        ],
        self::TAX_3 => [
            'description'    => 'Tax description 3',
            'customers'      => [LoadCustomers::CUSTOMER_LEVEL_1_1],
            'customerGroups' => []
        ],
        self::TAX_4 => [
            'description'    => 'Tax description 4',
            'customers'      => [],
            'customerGroups' => [LoadGroups::GROUP3]
        ]
    ];

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadCustomers::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $user = $this->getFirstUser($manager);
        $organization = $user->getOrganization();
        foreach (self::DATA as $code => $item) {
            $customerTaxCode = new CustomerTaxCode();
            $customerTaxCode->setCode($code);
            $customerTaxCode->setDescription($item['description']);
            $customerTaxCode->setOwner($user);
            $customerTaxCode->setOrganization($organization);
            foreach ($item['customers'] as $customerRef) {
                /** @var Customer $customer */
                $customer = $this->getReference($customerRef);
                $customer->setTaxCode($customerTaxCode);
            }
            foreach ($item['customerGroups'] as $customerGroupRef) {
                /** @var CustomerGroup $customer */
                $customerGroup = $this->getReference($customerGroupRef);
                $customerGroup->setTaxCode($customerTaxCode);
            }
            $manager->persist($customerTaxCode);
            $this->addReference(self::REFERENCE_PREFIX . '.' . $code, $customerTaxCode);
        }
        $manager->flush();
    }
}
