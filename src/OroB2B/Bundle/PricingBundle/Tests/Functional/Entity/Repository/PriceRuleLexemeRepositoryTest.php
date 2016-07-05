<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;

/**
 * @dbIsolation
 */
class PriceRuleLexemeRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadPriceRuleLexemes::class]);
    }

    public function testGetLexemesByRules()
    {
        /** @var PriceRule[] $rules */
        $rules = [$this->getReference('price_rule_1'), $this->getReference('price_rule_2')];

        $lexemes = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BPricingBundle:PriceRuleLexeme');
//            ->getLexemesByRules($rules);
    }
}
