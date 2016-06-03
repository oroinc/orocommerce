<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class PriceListAccountGroupFallbackRepositoryTest extends AbstractFallbackRepositoryTest
{
    /**
     * @dataProvider getAccountIdentityByWebsiteDataProvider
     * @param string $websiteReference
     * @param $expectedAccounts
     */
    public function testGetAccountIdentityByWebsite($websiteReference, $expectedAccounts)
    {
        /** @var Website $website */
        $website = $this->getReference($websiteReference);
        $iterator = $this->doctrine->getRepository('OroB2BPricingBundle:PriceListAccountGroupFallback')
            ->getAccountIdentityByWebsite($website->getId());
        $this->checkExpectedAccounts($expectedAccounts, $iterator);
    }

    /**
     * @return array
     */
    public function getAccountIdentityByWebsiteDataProvider()
    {
        return [
            'case1' => [
                'website' => 'US',
                'expectedAccounts' => [
                    'account.level_1_1',
                    'account.level_1.3',
                    'account.orphan',
                    'account.level_1.1',
                    'account.level_1.1.1',
                    'account.level_1.4.1',
                    'account.level_1.4.1.1',
                    'account.level_1',
                    'account.level_1.3.1',
                    'account.level_1.3.1.1',
                    'account.level_1.4',
                ],
            ],
            'case2' => [
                'website' => 'Canada',
                'expectedAccounts' => [
                    'account.level_1_1',
                    'account.level_1.3',
                    'account.orphan',
                    'account.level_1.1',
                    'account.level_1.1.1',
                    'account.level_1.4.1',
                    'account.level_1.4.1.1',
                    'account.level_1',
                    'account.level_1.3.1',
                    'account.level_1.3.1.1',
                    'account.level_1.4',
                ],
            ],
        ];
    }
}
