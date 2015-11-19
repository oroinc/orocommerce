<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
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

        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists']);
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
                    'priceList3'
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
     * @return PriceListToAccountRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BPricingBundle:PriceListToAccount');
    }
}
