<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerGroupFallbackRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class PriceListCustomerGroupFallbackRepositoryTest extends AbstractFallbackRepositoryTest
{
    /**
     * @dataProvider getCustomerIdentityByWebsiteDataProvider
     * @param string $websiteReference
     * @param $expectedCustomers
     */
    public function testGetCustomerIdentityByWebsite($websiteReference, $expectedCustomers)
    {
        /** @var Website $website */
        $website = $this->getReference($websiteReference);
        $iterator = $this->doctrine->getRepository(PriceListCustomerGroupFallback::class)
            ->getCustomerIdentityByWebsite($website->getId());
        $this->checkExpectedCustomers($expectedCustomers, $iterator);
    }

    /**
     * @return array
     */
    public function getCustomerIdentityByWebsiteDataProvider()
    {
        return [
            'case1' => [
                'website' => 'US',
                'expectedCustomers' => [
                    'customer.level_1_1',
                    'customer.level_1.3',
                    'CustomerUser CustomerUser',
                    'customer.orphan',
                    'customer.level_1.1',
                    'customer.level_1.1.1',
                    'customer.level_1.1.2',
                    'customer.level_1.4.1',
                    'customer.level_1.4.1.1',
                    'customer.level_1',
                    'customer.level_1.3.1',
                    'customer.level_1.3.1.1',
                    'customer.level_1.4',
                ],
            ],
            'case2' => [
                'website' => 'Canada',
                'expectedCustomers' => [
                    'customer.level_1.3',
                    'customer.orphan',
                    'CustomerUser CustomerUser',
                    'customer.level_1.1',
                    'customer.level_1.1.1',
                    'customer.level_1.1.2',
                    'customer.level_1.4.1',
                    'customer.level_1.4.1.1',
                    'customer.level_1',
                    'customer.level_1.3.1',
                    'customer.level_1.3.1.1',
                    'customer.level_1.4',
                ],
            ],
        ];
    }

    /**
     * @dataProvider fallbackDataProvider
     * @param string $websiteReference
     * @param string $customerGroupReference
     * @param bool $expected
     */
    public function testHasFallbackOnNextLevel($websiteReference, $customerGroupReference, $expected)
    {
        /** @var Website $website */
        $website = $this->getReference($websiteReference);
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference($customerGroupReference);

        /** @var PriceListCustomerGroupFallbackRepository $repo */
        $repo = $this->doctrine->getRepository(PriceListCustomerGroupFallback::class);
        $this->assertEquals($expected, $repo->hasFallbackOnNextLevel($website, $customerGroup));
    }

    public function fallbackDataProvider(): array
    {
        return [
            'defined fallback to previous level' => ['US', 'customer_group.group1', true],
            'default fallback to previous level' => ['US', 'customer_group.group3', true],
            'default fallback to current level' => ['US', 'customer_group.group2', false]
        ];
    }
}
