<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PriceRuleTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new PriceRule(), [
            ['id', 42],
            ['currency', 'some string'],
            ['quantity', 3.1415926],
            ['productUnit', new ProductUnit()],
            ['ruleCondition', 'some string'],
            ['rule', 'some string'],
            ['priceList', new PriceList()],
            ['priority', 42]
        ]);
    }

    public function testAddPriceRule()
    {
        $priceRule = new PriceRule();
        $priceRuleLexeme = new PriceRuleLexeme();

        $priceRule->addLexeme($priceRuleLexeme);
        $this->assertSame($priceRuleLexeme->getPriceRule(), $priceRule);
        $this->assertSame($priceRule->getLexemes()->first(), $priceRuleLexeme);
    }

    public function testSetPriceRules()
    {
        $priceRule = new PriceRule();
        $lexeme1 = new PriceRuleLexeme();
        $lexeme2 = new PriceRuleLexeme();

        $priceRule->setLexemes(new ArrayCollection([$lexeme1, $lexeme2]));

        $this->assertCount(2, $priceRule->getLexemes());
    }

    public function testRemovePriceRule()
    {
        $priceRule = new PriceRule();
        $lexeme1 = new PriceRuleLexeme();
        $lexeme2 = new PriceRuleLexeme();

        $priceRule->setLexemes(new ArrayCollection([$lexeme1, $lexeme2]));

        $priceRule->removePriceRule($lexeme1);
        $this->assertCount(1, $priceRule->getLexemes());
        $this->assertSame($priceRule->getLexemes()->first(), $lexeme2);
    }
}
