<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PricingBundle\Model\PriceListIsReferentialCheckerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleLexemeRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;

/**
 * @dbIsolation
 */
class PriceRuleLexemeRepositoryTest extends WebTestCase
{
    /**
     * @var PriceRuleLexemeRepository
     */
    protected $repository;

    /**
     * @var PriceListIsReferentialCheckerInterface
     */
    protected $checker;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadPriceRuleLexemes::class]);

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroPricingBundle:PriceRuleLexeme');
    }

    public function testCountReferencesForRelation()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_attribute_price_list_1');
        $expectedCounters = [
            ['relationId' => null, 'relationCount' => 2],
            ['relationId' => $priceList->getId(), 'relationCount' => 1]
        ];
        $this->assertEquals($expectedCounters, $this->repository->countReferencesForRelation());

    }

    /**
     * @depends testCountReferencesForRelation
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
