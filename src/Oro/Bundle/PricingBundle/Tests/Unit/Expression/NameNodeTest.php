<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Expression;

use Oro\Bundle\PricingBundle\Expression\NameNode;

class NameNodeTest extends \PHPUnit_Framework_TestCase
{
    public function testNode()
    {
        $class = 'testClass';
        $field = 'field';
        $node = new NameNode($class, $field);
        $this->assertSame($class, $node->getContainer());
        $this->assertSame($field, $node->getField());

        $this->assertEquals([$node], $node->getNodes());
        $this->assertFalse($node->isBoolean());
    }
}
