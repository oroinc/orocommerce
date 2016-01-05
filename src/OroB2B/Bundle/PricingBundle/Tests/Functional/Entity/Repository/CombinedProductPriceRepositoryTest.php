<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;

/**
 * @dbIsolation
 */
class CombinedProductPriceRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
            ]
        );
    }

    /**
     * @dataProvider getPricesByPriceListOnMergeAllowedDataProvider
     * @param string $combinedPriceListReference
     * @param string $priceListReference
     * @param array $expectedData
     */
    public function testGetPricesByPriceListOnMergeAllowed(
        $combinedPriceListReference,
        $priceListReference,
        $expectedData
    ) {
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference($combinedPriceListReference);
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceListReference);
        $prices = $this->getRepository()
            ->getPricesByPriceListOnMergeAllowed($combinedPriceList, $priceList)
            ->orderBy('pp.unit,pp.productSku,pp.quantity,pp.value,pp.currency')
            ->getQuery()->getResult();
        foreach ($expectedData as $key => $productPriceData) {
            foreach ($productPriceData as $fieldName => $value) {
                $this->assertEquals($prices[$key][$fieldName], $value);
            }
            $this->assertEquals(1, $prices[$key]['merge']);
        }
    }

    /**
     * @return array
     */
    public function getPricesByPriceListOnMergeAllowedDataProvider()
    {
        return [
            [
                'combinedPriceListReference' => '1t_2f_3t',
                'priceListReference' => 'price_list_2',
                'expectedData' =>
                    [
                        [
                            'unitId' => 'bottle',
                            'currency' => 'USD',
                            'productSku' => 'product.2',
                            'quantity' => 14,
                            'value' => '12.2000',
                        ],
                        [
                            'unitId' => 'bottle',
                            'currency' => 'EUR',
                            'productSku' => 'product.2',
                            'quantity' => 24,
                            'value' => '16.5000',
                        ],
                        [
                            'unitId' => 'liter',
                            'currency' => 'USD',
                            'productSku' => 'product.1',
                            'quantity' => 15,
                            'value' => '12.2000',
                        ],
                        [
                            'unitId' => 'liter',
                            'currency' => 'USD',
                            'productSku' => 'product.2',
                            'quantity' => 13,
                            'value' => '12.2000',
                        ],
                    ],
            ],
        ];
    }

    /**
     * @dataProvider getPricesByPriceListOnMergeNotAllowedDataProvider
     * @param string $combinedPriceListReference
     * @param string $priceListReference
     * @param array $expectedData
     */
    public function testGetPricesByPriceListOnMergeNotAllowed(
        $combinedPriceListReference,
        $priceListReference,
        $expectedData
    ) {
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference($combinedPriceListReference);
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceListReference);
        $prices = $this->getRepository()
            ->getPricesByPriceListOnMergeNotAllowed($combinedPriceList, $priceList)
            ->orderBy('pp.unit,pp.productSku,pp.quantity,pp.value,pp.currency')
            ->getQuery()->getResult();
        foreach ($expectedData as $key => $productPriceData) {
            foreach ($productPriceData as $fieldName => $value) {
                $this->assertEquals($prices[$key][$fieldName], $value);
            }
            $this->assertEquals(0, $prices[$key]['merge']);
        }
    }

    /**
     * @return array
     */
    public function getPricesByPriceListOnMergeNotAllowedDataProvider()
    {
        return $this->getPricesByPriceListOnMergeAllowedDataProvider();
    }

    public function testInsertPrices()
    {
        $this->markTestSkipped('will be done after boolean fix in select');
        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference('1t_2f_3t');
        $combinedPriceListsToPriceList = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BPricingBundle:CombinedPriceListToPriceList')
            ->getPriceListsByCombined($combinedPriceList);
        $this->getRepository()->insertPrices(
            $combinedPriceListsToPriceList[0],
            $this->getContainer()->get('oro_entity.orm.insert_from_select_query_executor')
        );
    }

    /**
     * @return CombinedProductPriceRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BPricingBundle:CombinedProductPrice');
    }
}
