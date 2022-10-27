<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class PriceListCustomerGroupFallbackRepositoryTest extends AbstractFallbackRepositoryTest
{
    /**
     * @dataProvider getCustomerIdentityByWebsiteDataProvider
     */
    public function testGetCustomerIdentityByWebsite(string $websiteReference, array $expectedCustomers)
    {
        /** @var Website $website */
        $website = $this->getReference($websiteReference);
        $iterator = $this->doctrine->getRepository(PriceListCustomerGroupFallback::class)
            ->getCustomerIdentityByWebsite($website->getId());
        $this->checkExpectedCustomers($expectedCustomers, $iterator);
    }

    public function getCustomerIdentityByWebsiteDataProvider(): array
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
}
