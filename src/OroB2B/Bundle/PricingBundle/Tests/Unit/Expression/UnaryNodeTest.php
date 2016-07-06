<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Expression;

use OroB2B\Bundle\PricingBundle\Expression\UnaryNode;
use OroB2B\Bundle\PricingBundle\Expression\NodeInterface;

class UnaryNodeTest extends \PHPUnit_Framework_TestCase
{
    public function testNode()
    {
        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $subnode */
        $subnode = $this->getMock(NodeInterface::class);
        $subnode->expects($this->any())
            ->method('getNodes')
            ->willReturn([$subnode]);

        $operation = 'not';

        $node = new UnaryNode($subnode, $operation);
        $this->assertSame($subnode, $node->getNode());
        $this->assertEquals('not', $node->getOperation());

        $this->assertEquals([$node, $subnode], $node->getNodes());
    }
}
