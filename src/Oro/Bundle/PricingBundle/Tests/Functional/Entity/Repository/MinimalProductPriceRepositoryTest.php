<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadMinimalProductPrices;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\MinimalProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\MinimalProductPriceRepository;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class MinimalProductPriceRepositoryTest extends WebTestCase
{
    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertFromSelectQueryExecutor;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadCombinedProductPrices::class,
                LoadMinimalProductPrices::class,
            ]
        );
        $this->insertFromSelectQueryExecutor = $this->getContainer()
            ->get('oro_entity.orm.insert_from_select_query_executor');
    }

    public function testFindByWebsite()
    {
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $actual = $this->getMinimalProductPriceRepository()
            ->findByWebsite(
                $website->getId(),
                [$product1],
                $this->getReference('1f')->getId()
            );
        $expected = [
            [
                'product' => $product1->getId(),
                'value' => '10.0000',
                'currency' => 'USD',
                'unit' => 'liter',
                'cpl' => $this->getReference('1f')->getId(),
            ],
            [
                'product' => $product1->getId(),
                'value' => '11.0000',
                'currency' => 'EUR',
                'unit' => 'liter',
                'cpl' => $this->getReference('1t_2t_3t')->getId(),
            ],
            [
                'product' => $product1->getId(),
                'value' => '13.0000',
                'currency' => 'USD',
                'unit' => 'liter',
                'cpl' => $this->getReference('1t_2t_3t')->getId(),
            ],
            [
                'product' => $product1->getId(),
                'value' => '12.0000',
                'currency' => 'CA',
                'unit' => 'liter',
                'cpl' => $this->getReference('2t_3f_1t')->getId(),
            ],
        ];
        usort($expected, [$this, 'sort']);
        usort($actual, [$this, 'sort']);

        $this->assertEquals($expected, $actual);
    }

    public function testUpdateMinimalPrices()
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getReference('1f');
        $this->getMinimalProductPriceRepository()->updateMinimalPrices($this->insertFromSelectQueryExecutor, $cpl);

        /** @var CombinedProductPriceRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getRepository('OroPricingBundle:CombinedProductPrice');
        /** @var MinimalProductPrice[] $minPrices */
        $minPrices = $this->getMinimalProductPriceRepository()->findBy(['priceList' => $cpl]);

        foreach ($minPrices as $minPrice) {
            /** @var CombinedProductPrice $realMinPrice */
            $realMinPrice = $repository->findOneBy(
                [
                    'priceList' => $cpl,
                    'product' => $minPrice->getProduct(),
                    'currency' => $minPrice->getPrice()->getCurrency()
                ],
                ['value' => 'ASC']
            );
            $this->assertEquals($minPrice->getPrice(), $realMinPrice->getPrice());
        }
    }

    /**
     * @param array $a
     * @param array $b
     * @return bool
     */
    protected function sort(array $a, array $b)
    {
        if ($a['cpl'] === $b['cpl']) {
            return $a['currency'] > $b['currency'] ? 1 : 0;
        }

        return $a['cpl'] > $b['cpl'] ? 1 : 0;
    }

    /**
     * @return MinimalProductPriceRepository
     */
    protected function getMinimalProductPriceRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroPricingBundle:MinimalProductPrice');
    }
}
