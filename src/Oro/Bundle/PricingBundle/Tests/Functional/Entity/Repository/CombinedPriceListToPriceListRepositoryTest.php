<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CombinedPriceListToPriceListRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists',
            'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
        ]);
    }

    /**
     * @dataProvider priceListsByCombinedAndProductDataProvider
     * @param string $combinedPriceList
     * @param string $product
     * @param array $expectedPriceLists
     */
    public function testGetPriceListsByCombinedAndProduct($combinedPriceList, $product, $expectedPriceLists)
    {
        /**
         * @var $combinedPriceList CombinedPriceList
         * @var $product Product
         */
        $combinedPriceList = $this->getReference($combinedPriceList);
        $product = $this->getReference($product);
        $priceListsRelations = $this->getRepository()->getPriceListRelations($combinedPriceList, [$product]);

        if ($expectedPriceLists) {
            $actualPriceLists = array_map(
                function (CombinedPriceListToPriceList $relation) {
                    return $relation->getPriceList()->getId();
                },
                $priceListsRelations
            );
            $expectedPriceLists = array_map(
                function ($priceListReference) {
                    return $this->getReference($priceListReference)->getId();
                },
                $expectedPriceLists
            );
            $this->assertEquals($expectedPriceLists, $actualPriceLists);
        } else {
            $this->assertEmpty($priceListsRelations);
        }
    }

    /**
     * @return array
     */
    public function priceListsByCombinedAndProductDataProvider()
    {
        return [
            'test getting price lists 1' => [
                'combinedPriceList' => '1t_2t_3t',
                'product' => 'product-1',
                'expectedPriceLists' => ['price_list_1', 'price_list_2'],
            ],
            'test getting price lists 2' => [
                'combinedPriceList' => '1t_2t_3t',
                'product' => 'product-2',
                'expectedPriceLists' => ['price_list_1', 'price_list_2'],
            ],
            'test getting price lists 3' => [
                'combinedPriceList' => '2f_1t_3t',
                'product' => 'продукт-7',
                'expectedPriceLists' => [],
            ],
        ];
    }

    /**
     * @dataProvider cplByPriceListProductDataProvider
     * @param string $priceList
     * @param int $result
     */
    public function testGetCombinedPriceListsByActualPriceLists($priceList, $result)
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceList);

        $cPriceLists = $this->getRepository()->getCombinedPriceListsByActualPriceLists([$priceList]);
        $this->assertCount($result, $cPriceLists);
    }

    /**
     * @return array
     */
    public function cplByPriceListProductDataProvider()
    {
        return [
            [
                'priceList' => 'price_list_1',
                'result' => 4,
            ],
            [
                'priceList' => 'price_list_4',
                'result' => 0,
            ],
        ];
    }

    public function testGetPriceListIdsByCpls()
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getReference('1t_2t_3t');

        $this->assertEquals(
            [
                $this->getReference('price_list_1')->getId(),
                $this->getReference('price_list_2')->getId(),
                $this->getReference('price_list_3')->getId(),
            ],
            $this->getRepository()->getPriceListIdsByCpls([$cpl])
        );
    }

    /**
     * @return CombinedPriceListToPriceListRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroPricingBundle:CombinedPriceListToPriceList');
    }
}
