<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Expression;

use OroB2B\Bundle\PricingBundle\Expression\ExpressionLanguageConverter;
use OroB2B\Bundle\PricingBundle\Expression\ExpressionParser;

class ExpressionParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExpressionParser
     */
    protected $expressionParser;

    protected function setUp()
    {
        $this->expressionParser = new ExpressionParser(new ExpressionLanguageConverter());
    }

    public function testParseCondition()
    {
        $expression = "(PriceList.currency == 'USD' and Product.margin * 10% > 130*Product.category.minMargin)" .
            " || (Product.category == -Product.MSRP and not (Product.category matches 'cat'))";
        
        $usedLexems = $this->expressionParser->getUsedLexems($expression);
    }
}
