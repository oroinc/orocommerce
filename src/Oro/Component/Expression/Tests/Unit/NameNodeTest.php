<?php

namespace Oro\Component\Expression\Tests\Unit;

use Oro\Component\Expression\Node\NameNode;

class NameNodeTest extends \PHPUnit_Framework_TestCase
{
    public function testNode()
    {
        $class = 'testClass';
        $field = 'field';
        $node = new NameNode($class, $field);
        $this->assertSame($class, $node->getContainer());
        $this->assertSame($field, $node->getField());
        $this->assertSame($class, $node->getResolvedContainer());

        $this->assertEquals([$node], $node->getNodes());
        $this->assertFalse($node->isBoolean());
    }

    public function testNodeWithId()
    {
        $class = 'testClass';
        $field = 'field';
        $id = 42;
        $node = new NameNode($class, $field, 42);
        $this->assertSame($class, $node->getContainer());
        $this->assertSame($field, $node->getField());
        $this->assertSame($id, $node->getContainerId());
        $this->assertSame($class . '|' . $id, $node->getResolvedContainer());
    }
}
