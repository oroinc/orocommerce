<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerFallbackRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class PriceListCustomerFallbackRepositoryTest extends AbstractFallbackRepositoryTest
{
    /**
     * @dataProvider getCustomerIdentityByGroupDataProvider
     * @param string[] $customerGroupsReferences
     * @param string $websiteReference
     * @param string[] $expectedCustomers
     */
    public function testGetCustomerIdentityByGroup(
        array $customerGroupsReferences,
        $websiteReference,
        $expectedCustomers
    ) {
        $customerGroups = [];
        foreach ($customerGroupsReferences as $customerGroupsReference) {
            $customerGroups[] = $this->getReference($customerGroupsReference);
        }
        /** @var Website $website */
        $website = $this->getReference($websiteReference);
        $iterator = $this->doctrine->getRepository(PriceListCustomerFallback::class)
            ->getCustomerIdentityByGroup($customerGroups, $website->getId());
        $this->checkExpectedCustomers($expectedCustomers, $iterator);
    }

    /**
     * @return array
     */
    public function getCustomerIdentityByGroupDataProvider()
    {
        return [
            'case1' => [
                'groups' => ['customer_group.group1', 'customer_group.group2'],
                'website' => 'US',
                'expectedCustomers' => [
                    'customer.level_1',
                    'customer.level_1.3',
                    'customer.level_1.2.1',
                    'customer.level_1.2.1.1',
                ],
            ],
            'case2' => [
                'groups' => ['customer_group.group1', 'customer_group.group2'],
                'website' => 'Canada',
                'expectedCustomers' => [
                    'customer.level_1',
                    'customer.level_1.3',
                    'customer.level_1.2.1',
                    'customer.level_1.2.1.1',
                ],
            ],
            'case3' => [
                'groups' => ['customer_group.group1'],
                'website' => 'Canada',
                'expectedCustomers' => [
                    'customer.level_1',
                    'customer.level_1.3',
                ],
            ],
            'case4' => [
                'groups' => [],
                'website' => 'Canada',
                'expectedCustomers' => [],
            ],
        ];
    }

    /**
     * @dataProvider fallbackDataProvider
     * @param string $websiteReference
     * @param string $customerReference
     * @param bool $expected
     */
    public function testHasFallbackOnNextLevel($websiteReference, $customerReference, $expected)
    {
        /** @var Website $website */
        $website = $this->getReference($websiteReference);
        /** @var Customer $customer */
        $customer = $this->getReference($customerReference);

        /** @var PriceListCustomerFallbackRepository $repo */
        $repo = $this->doctrine->getRepository(PriceListCustomerFallback::class);
        $this->assertEquals($expected, $repo->hasFallbackOnNextLevel($website, $customer));
    }

    public function fallbackDataProvider(): array
    {
        return [
            'defined fallback to previous level' => ['US', 'customer.level_1_1', true],
            'default fallback to previous level' => ['US', 'customer.level_1.2.1', true],
            'default fallback to current level' => ['US', 'customer.level_1.2', false]
        ];
    }
}
