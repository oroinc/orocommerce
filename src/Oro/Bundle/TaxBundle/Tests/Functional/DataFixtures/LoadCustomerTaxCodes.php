<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;

class LoadCustomerTaxCodes extends AbstractFixture implements DependentFixtureInterface
{
    const TAX_1 = 'TAX1';
    const TAX_2 = 'TAX2';
    const TAX_3 = 'TAX3';
    const TAX_4 = 'TAX4';

    const DESCRIPTION_1 = 'Tax description 1';
    const DESCRIPTION_2 = 'Tax description 2';
    const DESCRIPTION_3 = 'Tax description 3';
    const DESCRIPTION_4 = 'Tax description 4';

    const REFERENCE_PREFIX = 'customer_tax_code';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createCustomerTaxCode(
            $manager,
            self::TAX_1,
            self::DESCRIPTION_1,
            [LoadCustomers::DEFAULT_ACCOUNT_NAME],
            []
        );
        $this->createCustomerTaxCode(
            $manager,
            self::TAX_3,
            self::DESCRIPTION_3,
            [LoadCustomers::CUSTOMER_LEVEL_1_1],
            []
        );
        $this->createCustomerTaxCode($manager, self::TAX_2, self::DESCRIPTION_2, [], [LoadGroups::GROUP2]);
        $this->createCustomerTaxCode($manager, self::TAX_4, self::DESCRIPTION_4, [], [LoadGroups::GROUP3]);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $code
     * @param string $description
     * @param array $customerRefs
     * @param array $customerGroupsRefs
     * @return CustomerTaxCode
     */
    protected function createCustomerTaxCode(
        ObjectManager $manager,
        $code,
        $description,
        array $customerRefs,
        array $customerGroupsRefs
    ) {
        $customerTaxCode = new CustomerTaxCode();
        $customerTaxCode->setCode($code);
        $customerTaxCode->setDescription($description);
        foreach ($customerRefs as $customerRef) {
            /** @var Customer $customer */
            $customer = $this->getReference($customerRef);
            $customerTaxCode->addCustomer($customer);
        }

        foreach ($customerGroupsRefs as $customerGroupRef) {
            /** @var CustomerGroup $customer */
            $customer = $this->getReference($customerGroupRef);
            $customerTaxCode->addCustomerGroup($customer);
        }

        $manager->persist($customerTaxCode);
        $this->addReference(self::REFERENCE_PREFIX . '.' . $code, $customerTaxCode);

        return $customerTaxCode;
    }
}
