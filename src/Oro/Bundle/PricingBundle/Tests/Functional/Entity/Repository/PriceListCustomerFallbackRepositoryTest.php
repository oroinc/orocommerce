<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class PriceListCustomerFallbackRepositoryTest extends AbstractFallbackRepositoryTest
{
    /**
     * @dataProvider getCustomerIdentityByGroupDataProvider
     */
    public function testGetCustomerIdentityByGroup(
        array $customerGroupsReferences,
        string $websiteReference,
        array $expectedCustomers
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

    public function getCustomerIdentityByGroupDataProvider(): array
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
}
