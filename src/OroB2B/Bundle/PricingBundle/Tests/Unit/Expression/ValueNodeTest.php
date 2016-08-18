<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Expression;

use OroB2B\Bundle\PricingBundle\Expression\ValueNode;

class ValueNodeTest extends \PHPUnit_Framework_TestCase
{
    public function testNode()
    {
        $value = 100;
        $node = new ValueNode($value);
        $this->assertSame($value, $node->getValue());

        $this->assertEquals([$node], $node->getNodes());
        $this->assertFalse($node->isBoolean());
    }
}
