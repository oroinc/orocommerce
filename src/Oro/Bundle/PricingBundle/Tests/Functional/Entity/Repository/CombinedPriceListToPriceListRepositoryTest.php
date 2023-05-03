<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToPriceListRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceListsForFallback;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class CombinedPriceListToPriceListRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    /**
     * @dataProvider priceListsByCombinedAndProductDataProvider
     */
    public function testGetPriceListsByCombinedAndProduct(
        string $combinedPriceList,
        string $product,
        array $expectedPriceLists
    ) {
        $this->loadFixtures([
            LoadCombinedPriceLists::class,
            LoadProductPrices::class,
        ]);

        /** @var CombinedPriceList $combinedPriceList */
        $combinedPriceList = $this->getReference($combinedPriceList);
        /** @var Product $product */
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

    public function priceListsByCombinedAndProductDataProvider(): array
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
     */
    public function testGetCombinedPriceListsByActualPriceLists(string $priceList, int $result)
    {
        $this->loadFixtures([
            LoadCombinedPriceLists::class,
            LoadProductPrices::class,
        ]);

        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceList);

        $cPriceLists = $this->getRepository()->getCombinedPriceListsByActualPriceLists([$priceList]);
        $this->assertCount($result, $cPriceLists);
    }

    public function cplByPriceListProductDataProvider(): array
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
        $this->loadFixtures([
            LoadCombinedPriceLists::class,
            LoadProductPrices::class,
        ]);

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
     * @dataProvider fallbackCplDataProvider
     */
    public function testFindFallbackCpl(string $cplReference, ?string $expectedCplReference = null)
    {
        $this->loadFixtures([
            LoadCombinedPriceListsForFallback::class
        ]);

        $cpl = $this->getReference($cplReference);
        $fallbackCpl = $this->getRepository()->findFallbackCpl($cpl);
        $this->assertFallbackCpl($expectedCplReference, $fallbackCpl);
    }

    public function fallbackCplDataProvider(): \Generator
    {
        yield 'longest calculated not blocked fallback used' => ['1t_2t_3t_4t_5t_6t', '1t_3t_4t'];
        yield 'longest calculated not blocked fallback consist of 1 PL and shouldn\'t be used' => ['1t_3t_4t', null];
    }

    /**
     * @dataProvider fallbackCplWithMergeFlagDataProvider
     */
    public function testFindFallbackCplUsingMergeFlag(string $cplReference, ?string $expectedCplReference = null)
    {
        $this->loadFixtures([
            LoadCombinedPriceListsForFallback::class
        ]);

        $cpl = $this->getReference($cplReference);
        $fallbackCpl = $this->getRepository()->findFallbackCplUsingMergeFlag($cpl);
        $this->assertFallbackCpl($expectedCplReference, $fallbackCpl);
    }

    public function fallbackCplWithMergeFlagDataProvider(): \Generator
    {
        yield 'longest calculated not blocked fallback used' => ['1t_2t_3t_4t_5t_6t', '1t_2t'];
        //yield 'longest calculated not blocked fallback consist of 1 PL and shouldn\'t be used' => ['1t_3t_4t', null];
    }

    private function getRepository(): CombinedPriceListToPriceListRepository
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getRepository(CombinedPriceListToPriceList::class);
    }

    private function assertFallbackCpl(?string $expectedCplReference, ?CombinedPriceList $fallbackCpl): void
    {
        $expectedFallback = null;
        $dbPlatform = $this->getContainer()->get('doctrine')->getConnection()->getDatabasePlatform();
        if ($dbPlatform instanceof PostgreSQL94Platform) {
            $expectedFallback = $expectedCplReference ? $this->getReference($expectedCplReference) : null;
        }

        if ($expectedFallback === null) {
            $this->assertNull($fallbackCpl);
        } else {
            $this->assertNotNull($fallbackCpl);
            $this->assertEquals($expectedFallback->getId(), $fallbackCpl->getId());
        }
    }
}
