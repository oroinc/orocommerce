<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\MinimalProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\MinimalProductPriceRepository;

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
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists',
            'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices',
        ]);
        $this->insertFromSelectQueryExecutor = $this->getContainer()
            ->get('oro_entity.orm.insert_from_select_query_executor');
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
     * @return MinimalProductPriceRepository
     */
    protected function getMinimalProductPriceRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroPricingBundle:MinimalProductPrice');
    }
}
