<?php

namespace Oro\Component\Expression\Tests\Unit;

use Oro\Component\Expression\Node\RelationNode;

class RelationNodeTest extends \PHPUnit_Framework_TestCase
{
    public function testNode()
    {
        $class = 'testClass';
        $relationField = 'field';
        $field = 'relation';
        $node = new RelationNode($class, $field, $relationField);
        $this->assertSame($class, $node->getContainer());
        $this->assertSame($relationField, $node->getRelationField());
        $this->assertSame($field, $node->getField());
        $this->assertEquals('testClass::relation', $node->getRelationAlias());
        $this->assertEquals('testClass::relation', $node->getResolvedContainer());

        $this->assertEquals([$node], $node->getNodes());
        $this->assertFalse($node->isBoolean());
    }

    public function testNodeWithId()
    {
        $class = 'testClass';
        $relationField = 'field';
        $field = 'relation';
        $id = 42;
        $node = new RelationNode($class, $field, $relationField, $id);
        $this->assertSame($class, $node->getContainer());
        $this->assertSame($relationField, $node->getRelationField());
        $this->assertSame($field, $node->getField());
        $this->assertSame($id, $node->getContainerId());
        $this->assertEquals('testClass::relation|42', $node->getResolvedContainer());
    }
}
