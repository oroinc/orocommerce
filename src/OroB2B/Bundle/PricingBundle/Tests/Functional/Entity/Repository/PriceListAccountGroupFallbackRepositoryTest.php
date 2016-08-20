<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\WebsiteBundle\Entity\Website;

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
        $iterator = $this->doctrine->getRepository('OroPricingBundle:PriceListAccountGroupFallback')
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
                    'AccountUser AccountUser',
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
                    'AccountUser AccountUser',
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
