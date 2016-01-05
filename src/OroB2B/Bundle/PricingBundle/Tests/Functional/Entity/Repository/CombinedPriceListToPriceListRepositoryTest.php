<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;

/**
 * @dbIsolation
 */
class CombinedPriceListToPriceListRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists']);
    }

    /**
     * @dataProvider getPriceListsByCombinedDataProvider
     * @param string $combinedPriceListReference
     * @param array $expectedData
     */
    public function testGetPriceListsByCombined($combinedPriceListReference, $expectedData)
    {
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference($combinedPriceListReference);
        $combinedPriceListsToPriceList = $this->getRepository()->getPriceListsByCombined($combinedPriceList);
        foreach ($expectedData as $key => $combinedPriceListToPriceListData) {
            $combinedPriceListToPriceList = $combinedPriceListsToPriceList[$key];
            $this->assertEquals(
                spl_object_hash($combinedPriceListToPriceList->getPriceList()),
                spl_object_hash($this->getReference($combinedPriceListToPriceListData['priceListReference']))
            );
            $this->assertEquals(
                spl_object_hash($combinedPriceListToPriceList->getCombinedPriceList()),
                spl_object_hash($combinedPriceList)
            );
            $this->assertEquals(
                $combinedPriceListToPriceList->isMergeAllowed(),
                $combinedPriceListToPriceListData['merge']
            );
        }
    }

    public function getPriceListsByCombinedDataProvider()
    {
        return [
            [
                'combinedPriceListReference' => '1t_2f_3t',
                'expectedData' =>
                    [
                        ['priceListReference' => 'price_list_1', 'merge' => true],
                        ['priceListReference' => 'price_list_2', 'merge' => false],
                        ['priceListReference' => 'price_list_3', 'merge' => true],
                    ],
            ],
            [
                'combinedPriceListReference' => '3f_4t_2f',
                'expectedData' =>
                    [
                        ['priceListReference' => 'price_list_3', 'merge' => false],
                        ['priceListReference' => 'price_list_4', 'merge' => true],
                        ['priceListReference' => 'price_list_2', 'merge' => false],
                    ],
            ],
        ];
    }

    /**
     * @return CombinedPriceListToPriceListRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BPricingBundle:CombinedPriceListToPriceList');
    }
}
