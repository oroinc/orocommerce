<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class PriceListToAccountGroupRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists']);
    }

    /**
     * @dataProvider getPriceListDataProvider
     * @param string $accountGroup
     * @param string $website
     * @param array $expectedPriceLists
     */
    public function testGetPriceLists($accountGroup, $website, array $expectedPriceLists)
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference($accountGroup);
        /** @var Website $website */
        $website = $this->getReference($website);

        $actualPriceListsToAccountGroup = $this->getRepository()->getPriceLists($accountGroup, $website);

        $actualPriceLists = array_map(
            function (PriceListToAccountGroup $priceListToAccountGroup) {
                return $priceListToAccountGroup->getPriceList()->getName();
            },
            $actualPriceListsToAccountGroup
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
                'account' => 'account_group.group1',
                'website' => 'US',
                'expectedPriceLists' => [
                    'priceList1'
                ]
            ],
            [
                'account' => 'account_group.group2',
                'website' => 'US',
                'expectedPriceLists' => [
                    'priceList4'
                ]
            ],
            [
                'account' => 'account_group.group3',
                'website' => 'Canada',
                'expectedPriceLists' => [
                    'priceList5'
                ]
            ],
        ];
    }

    /**
     * @return PriceListToAccountGroupRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BPricingBundle:PriceListToAccountGroup');
    }
}
