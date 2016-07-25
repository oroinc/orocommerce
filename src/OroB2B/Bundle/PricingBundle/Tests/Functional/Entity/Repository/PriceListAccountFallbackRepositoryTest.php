<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class PriceListAccountFallbackRepositoryTest extends AbstractFallbackRepositoryTest
{
    /**
     * @dataProvider getAccountIdentityByGroupDataProvider
     * @param string[] $accountGroupsReferences
     * @param string $websiteReference
     * @param string[] $expectedAccounts
     */
    public function testGetAccountIdentityByGroup(array $accountGroupsReferences, $websiteReference, $expectedAccounts)
    {
        $accountGroups = [];
        foreach ($accountGroupsReferences as $accountGroupsReference) {
            $accountGroups[] = $this->getReference($accountGroupsReference);
        }
        /** @var Website $website */
        $website = $this->getReference($websiteReference);
        $iterator = $this->doctrine->getRepository('OroB2BPricingBundle:PriceListAccountFallback')
            ->getAccountIdentityByGroup($accountGroups, $website->getId());
        $this->checkExpectedAccounts($expectedAccounts, $iterator);
    }

    /**
     * @return array
     */
    public function getAccountIdentityByGroupDataProvider()
    {
        return [
            'case1' => [
                'groups' => ['account_group.group1', 'account_group.group2'],
                'website' => 'US',
                'expectedAccounts' => [
                    'account.level_1',
                    'account.level_1.3',
                    'account.level_1.2.1',
                    'account.level_1.2.1.1',
                ],
            ],
            'case2' => [
                'groups' => ['account_group.group1', 'account_group.group2'],
                'website' => 'Canada',
                'expectedAccounts' => [
                    'account.level_1',
                    'account.level_1.3',
                    'account.level_1.2.1',
                    'account.level_1.2.1.1',
                ],
            ],
            'case3' => [
                'groups' => ['account_group.group1'],
                'website' => 'Canada',
                'expectedAccounts' => [
                    'account.level_1',
                    'account.level_1.3',
                ],
            ],
            'case4' => [
                'groups' => [],
                'website' => 'Canada',
                'expectedAccounts' => [],
            ],
        ];
    }
}
