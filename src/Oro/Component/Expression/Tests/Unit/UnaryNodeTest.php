<?php

namespace Oro\Component\Expression\Tests\Unit;

use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\Node\UnaryNode;

class UnaryNodeTest extends \PHPUnit\Framework\TestCase
{
    public function testNode()
    {
        /** @var NodeInterface|\PHPUnit\Framework\MockObject\MockObject $subnode */
        $subnode = $this->createMock(NodeInterface::class);
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
        /** @var NodeInterface|\PHPUnit\Framework\MockObject\MockObject $subnode */
        $subnode = $this->createMock(NodeInterface::class);
        $subnode->expects($this->once())
            ->method('isBoolean')
            ->willReturn(true);

        $node = new UnaryNode($subnode, 'not');
        $this->assertTrue($node->isBoolean());
    }

    public function testIsBooleanIsNotNot()
    {
        /** @var NodeInterface|\PHPUnit\Framework\MockObject\MockObject $subnode */
        $subnode = $this->createMock(NodeInterface::class);
        $subnode->expects($this->any())
            ->method('isBoolean')
            ->willReturn(true);

        $node = new UnaryNode($subnode, '-');
        $this->assertFalse($node->isBoolean());
    }

    public function testIsBooleanFalseSub()
    {
        /** @var NodeInterface|\PHPUnit\Framework\MockObject\MockObject $subnode */
        $subnode = $this->createMock(NodeInterface::class);
        $subnode->expects($this->any())
            ->method('isBoolean')
            ->willReturn(false);

        $node = new UnaryNode($subnode, 'not');
        $this->assertFalse($node->isBoolean());
    }
}
