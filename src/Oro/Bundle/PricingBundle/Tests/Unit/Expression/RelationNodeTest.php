<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Expression;

use Oro\Bundle\PricingBundle\Expression\RelationNode;

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

        $this->assertEquals([$node], $node->getNodes());
        $this->assertFalse($node->isBoolean());
    }
}
