<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleLexemeRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class PriceRuleLexemeRepositoryTest extends WebTestCase
{
    /**
     * @var PriceRuleLexemeRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadPriceRuleLexemes::class]);

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository(PriceRuleLexeme::class);
    }

    public function testGetRelationIds()
    {
        /** @var PriceList $priceList1 */
        $priceList1 = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        /** @var PriceList $priceList2 */
        $priceList2 = $this->getReference(LoadPriceLists::PRICE_LIST_2);
        $relationIds = $this->repository->getRelationIds();
        sort($relationIds);
        $expected = [$priceList1->getId(), $priceList2->getId()];
        $this->assertEquals($expected, $relationIds);
    }

    /**
     * @depends testGetRelationIds
     */
    public function testDeleteByPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        // Check existing lexemes for price list
        $lexemes = $this->repository->findBy(['priceList' => $priceList]);
        $this->assertNotEmpty($lexemes);

        $this->repository->deleteByPriceList($priceList);

        // Check Lexemes for price list are empty
        $lexemes = $this->repository->findBy(['priceList' => $priceList]);
        $this->assertEmpty($lexemes);
    }
}
