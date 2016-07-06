<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Expression;

use OroB2B\Bundle\PricingBundle\Expression\RelationNode;

class RelationNodeTest extends \PHPUnit_Framework_TestCase
{
    public function testNode()
    {
        $class = 'testClass';
        $relationField = 'relation';
        $field = 'field';
        $node = new RelationNode($class, $field, $relationField);
        $this->assertSame($class, $node->getContainer());
        $this->assertSame($relationField, $node->getRelationField());
        $this->assertSame($field, $node->getField());

        $this->assertEquals([$node], $node->getNodes());
    }
}
