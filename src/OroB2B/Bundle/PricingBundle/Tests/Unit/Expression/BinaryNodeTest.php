<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Expression;

use OroB2B\Bundle\PricingBundle\Expression\BinaryNode;
use OroB2B\Bundle\PricingBundle\Expression\NodeInterface;

class BinaryNodeTest extends \PHPUnit_Framework_TestCase
{
    public function testNode()
    {
        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $left */
        $left = $this->getMock(NodeInterface::class);
        $left->expects($this->any())
            ->method('getNodes')
            ->willReturn([$left]);

        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $right */
        $right = $this->getMock(NodeInterface::class);
        $right->expects($this->any())
            ->method('getNodes')
            ->willReturn([$right]);
        $operation = '||';

        $node = new BinaryNode($left, $right, $operation);
        $this->assertSame($left, $node->getLeft());
        $this->assertSame($right, $node->getRight());
        $this->assertEquals('or', $node->getOperation());

        $this->assertEquals([$node, $left, $right], $node->getNodes());
    }
}
