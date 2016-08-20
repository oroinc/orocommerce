<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Expression;

use Oro\Bundle\PricingBundle\Expression\UnaryNode;
use Oro\Bundle\PricingBundle\Expression\NodeInterface;

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

    public function testIsBooleanTrue()
    {
        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $subnode */
        $subnode = $this->getMock(NodeInterface::class);
        $subnode->expects($this->once())
            ->method('isBoolean')
            ->willReturn(true);

        $node = new UnaryNode($subnode, 'not');
        $this->assertTrue($node->isBoolean());
    }

    public function testIsBooleanIsNotNot()
    {
        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $subnode */
        $subnode = $this->getMock(NodeInterface::class);
        $subnode->expects($this->any())
            ->method('isBoolean')
            ->willReturn(true);

        $node = new UnaryNode($subnode, '-');
        $this->assertFalse($node->isBoolean());
    }

    public function testIsBooleanFalseSub()
    {
        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $subnode */
        $subnode = $this->getMock(NodeInterface::class);
        $subnode->expects($this->any())
            ->method('isBoolean')
            ->willReturn(false);

        $node = new UnaryNode($subnode, 'not');
        $this->assertFalse($node->isBoolean());
    }
}
