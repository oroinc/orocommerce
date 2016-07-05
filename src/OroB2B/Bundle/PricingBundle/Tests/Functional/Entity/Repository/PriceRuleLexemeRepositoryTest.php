<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleLexeme;
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
        /** @var PriceRuleLexeme[] $expectedLexemes */
        $expectedLexemes = [$this->getReference('lexeme_1'), $this->getReference('lexeme_2')];
        $lexemes = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BPricingBundle:PriceRuleLexeme')
            ->getLexemesByRules($rules);
        $this->assertCount(count($expectedLexemes), $lexemes);
        foreach ($lexemes as $lexeme) {
            $this->assertTrue($this->exist($lexeme, $expectedLexemes));
        }
    }

    /**
     * @param PriceRuleLexeme $lexeme
     * @param PriceRuleLexeme[] $expectedLexemes
     * @return bool
     */
    protected function exist(PriceRuleLexeme $lexeme, $expectedLexemes)
    {
        foreach ($expectedLexemes as $expectedLexeme) {
            if ($lexeme->getId() === $expectedLexeme->getId()) {
                return true;
            }
        }

        return false;
    }
}
