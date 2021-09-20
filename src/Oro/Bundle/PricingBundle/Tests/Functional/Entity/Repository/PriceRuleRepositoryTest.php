<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceRuleRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRules;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PriceRuleRepositoryTest extends WebTestCase
{
    private PriceRuleRepository $repo;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadPriceRules::class
            ]
        );
        $this->repo = $this->getContainer()->get('doctrine')->getRepository(PriceRule::class);
    }

    public function testGetRuleIds()
    {
        $expected = [
            $this->getReference(LoadPriceRules::PRICE_RULE_1)->getId(),
            $this->getReference(LoadPriceRules::PRICE_RULE_2)->getId(),
            $this->getReference(LoadPriceRules::PRICE_RULE_3)->getId(),
            $this->getReference(LoadPriceRules::PRICE_RULE_4)->getId(),
            $this->getReference(LoadPriceRules::PRICE_RULE_5)->getId()
        ];

        $actual = $this->repo->getRuleIds();
        $this->assertEqualsCanonicalizing($expected, $actual);
    }
}
