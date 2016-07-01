<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Model;

use OroB2B\Bundle\PricingBundle\Model\ExpressionParser;

class ExpressionParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExpressionParser
     */
    protected $expressionParser;

    protected function setUp()
    {
        $this->expressionParser = new ExpressionParser();
    }

    public function testParseCondition()
    {
        $expression = "Product[1].currency == 'USD'";

        $this->expressionParser->parse($expression);
    }
}
