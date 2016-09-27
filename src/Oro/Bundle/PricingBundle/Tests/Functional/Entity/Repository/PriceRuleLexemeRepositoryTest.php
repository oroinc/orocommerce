<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

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

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadPriceRuleLexemes::class]);

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroPricingBundle:PriceRuleLexeme');
    }

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
