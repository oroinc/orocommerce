<?php

namespace Oro\Component\Expression\Tests\Unit;

use Oro\Component\Expression\Node\ValueNode;

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
