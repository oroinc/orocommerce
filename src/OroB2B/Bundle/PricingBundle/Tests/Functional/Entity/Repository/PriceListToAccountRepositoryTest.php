<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Model\DTO\AccountWebsiteDTO;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class PriceListToAccountRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings',
            ]
        );
    }

    public function testFindByPrimaryKey()
    {
        $repository = $this->getRepository();

        /** @var PriceListToAccount $actualPriceListToAccount */
        $actualPriceListToAccount = $repository->findOneBy([]);

        $expectedPriceListToAccount = $repository->findByPrimaryKey(
            $actualPriceListToAccount->getPriceList(),
            $actualPriceListToAccount->getAccount(),
            $actualPriceListToAccount->getWebsite()
        );

        $this->assertEquals(spl_object_hash($expectedPriceListToAccount), spl_object_hash($actualPriceListToAccount));
    }

    /**
     * @dataProvider getPriceListDataProvider
     * @param string $account
     * @param string $website
     * @param array $expectedPriceLists
     */
    public function testGetPriceLists($account, $website, array $expectedPriceLists)
    {
        /** @var Account $account */
        $account = $this->getReference($account);
        /** @var Website $website */
        $website = $this->getReference($website);

        $actualPriceListsToAccount = $this->getRepository()->getPriceLists($account, $website);

        $actualPriceLists = array_map(
            function (PriceListToAccount $priceListToAccount) {
                return $priceListToAccount->getPriceList()->getName();
            },
            $actualPriceListsToAccount
        );

        $this->assertEquals($expectedPriceLists, $actualPriceLists);
    }

    /**
     * @return array
     */
    public function getPriceListDataProvider()
    {
        return [
            [
                'account' => 'account.level_1.2',
                'website' => 'US',
                'expectedPriceLists' => [
                    'priceList2'
                ]
            ],
            [
                'account' => 'account.orphan',
                'website' => 'US',
                'expectedPriceLists' => [
                ]
            ],
            [
                'account' => 'account.level_1_1',
                'website' => 'US',
                'expectedPriceLists' => [
                    'priceList2',
                    'priceList1'
                ]
            ],
            [
                'account' => 'account.level_1.1.1',
                'website' => 'Canada',
                'expectedPriceLists' => [
                    'priceList5'
                ]
            ],
        ];
    }

    /**
     * @dataProvider getPriceListIteratorDataProvider
     * @param string $accountGroup
     * @param string $website
     * @param array $expectedAccounts
     * @param int $fallback
     */
    public function testGetAccountIteratorByFallback($accountGroup, $website, $expectedAccounts, $fallback = null)
    {
        /** @var $accountGroup  AccountGroup */
        $accountGroup = $this->getReference($accountGroup);
        /** @var $website Website */
        $website = $this->getReference($website);

        $iterator = $this->getRepository()
            ->getAccountIteratorByDefaultFallback($accountGroup, $website, $fallback);

        $actualSiteMap = [];
        foreach ($iterator as $account) {
            $actualSiteMap[] = $account->getName();
        }
        $this->assertSame($expectedAccounts, $actualSiteMap);
    }

    /**
     * @return array
     */
    public function getPriceListIteratorDataProvider()
    {
        return [
            'with fallback group1' => [
                'accountGroup' => 'account_group.group1',
                'website' => 'US',
                'expectedAccounts' => ['account.level_1.3'],
                'fallback' => PriceListAccountFallback::ACCOUNT_GROUP
            ],
            'without fallback group1' => [
                'accountGroup' => 'account_group.group1',
                'website' => 'US',
                'expectedAccounts' => ['account.level_1.3']
            ],
            'with fallback group2' => [
                'accountGroup' => 'account_group.group2',
                'website' => 'US',
                'expectedAccounts' => [],
                'fallback' => PriceListAccountFallback::ACCOUNT_GROUP
            ],
            'without fallback group2' => [
                'accountGroup' => 'account_group.group2',
                'website' => 'US',
                'expectedAccounts' => ['account.level_1.2'],
            ],
        ];
    }


    public function testGetAccountWebsitePairsByAccountGroup()
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference('account_group.group1');
        /** @var Account $account */
        $account = $this->getReference('account.level_1.3');
        /** @var Website $website */
        $website = $this->getReference('US');
        $result = $this->getRepository()->getAccountWebsitePairsByAccountGroup(
            $accountGroup,
            [$website->getId()]
        );
        $this->assertCount(1, $result);
        $result = $result[0];
        $this->assertEquals($result->getAccount()->getId(), $account->getId());
        $this->assertEquals($result->getWebsite()->getId(), $website->getId());
    }

    public function testGetAccountWebsitePairsByAccount()
    {
        /** @var Account $account */
        $account = $this->getReference('account.level_1_1');
        /** @var Website $website */
        $website = $this->getReference('US');
        /** @var AccountWebsiteDTO[] $result */
        $result = $this->getRepository()->getAccountWebsitePairsByAccount($account);
        $this->assertCount(1, $result);
        $this->assertEquals($result[0]->getAccount()->getId(), $account->getId());
        $this->assertEquals($result[0]->getWebsite()->getId(), $website->getId());
    }

    public function testDelete()
    {
        /** @var Account $account */
        $account = $this->getReference('account.level_1_1');
        /** @var Website $website */
        $website = $this->getReference('US');
        $this->assertCount(7, $this->getRepository()->findAll());
        $this->assertCount(2, $this->getRepository()->findBy(['account' => $account, 'website' => $website]));
        $this->getRepository()->delete($account, $website);
        $this->assertCount(5, $this->getRepository()->findAll());
        $this->assertCount(0, $this->getRepository()->findBy(['account' => $account, 'website' => $website]));
    }

    /**
     * @return PriceListToAccountRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BPricingBundle:PriceListToAccount');
    }
}
