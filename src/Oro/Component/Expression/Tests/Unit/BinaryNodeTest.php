<?php

namespace Oro\Component\Expression\Tests\Unit;

use Oro\Component\Expression\Node\BinaryNode;
use Oro\Component\Expression\Node\NodeInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
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

    /**
     * @dataProvider booleanOperationsDataProvider
     * @param string $operation
     */
    public function testIsBooleanOperation($operation)
    {
        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $left */
        $left = $this->getMock(NodeInterface::class);
        $left->expects($this->never())
            ->method('isBoolean');
        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $right */
        $right = $this->getMock(NodeInterface::class);
        $right->expects($this->never())
            ->method('isBoolean');
        $node = new BinaryNode($left, $right, $operation);
        $this->assertTrue($node->isBoolean());
    }

    /**
     * @return array
     */
    public function booleanOperationsDataProvider()
    {
        return [
            ['and'],
            ['or']
        ];
    }

    /**
     * @dataProvider booleanExpressionsDataProvider
     * @param string $operation
     */
    public function testIsBooleanExpression($operation)
    {
        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $left */
        $left = $this->getMock(NodeInterface::class);
        $left->expects($this->never())
            ->method('isBoolean');
        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $right */
        $right = $this->getMock(NodeInterface::class);
        $right->expects($this->never())
            ->method('isBoolean');

        $node = new BinaryNode($left, $right, $operation);
        $this->assertTrue($node->isBoolean());
    }

    /**
     * @return array
     */
    public function booleanExpressionsDataProvider()
    {
        return [
            ['>'],
            ['>='],
            ['<'],
            ['<='],
            ['=='],
            ['!='],
            ['like'],
        ];
    }

    /**
     * @dataProvider leftRightBooleanDataProvider
     * @param bool $leftBoolean
     * @param bool $rightBoolean
     * @param bool $expected
     */
    public function testIsBooleanForMathExpression($leftBoolean, $rightBoolean, $expected)
    {
        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $left */
        $left = $this->getMock(NodeInterface::class);
        $left->expects($this->any())
            ->method('isBoolean')
            ->willReturn($leftBoolean);
        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $right */
        $right = $this->getMock(NodeInterface::class);
        $right->expects($this->any())
            ->method('isBoolean')
            ->willReturn($rightBoolean);

        $node = new BinaryNode($left, $right, '+');
        $this->assertEquals($expected, $node->isBoolean());
    }

    /**
     * @return array
     */
    public function leftRightBooleanDataProvider()
    {
        return [
            'is boolean when left and right boolean' => [true, true, true],
            'is boolean when left is boolean and right is not' => [true, false, true],
            'is boolean when right is boolean and left is not' => [false, true, true],
            'is not boolean when left and right are not boolean' => [false, false, false]
        ];
    }

    /**
     * @dataProvider booleanExpressionsDataProvider
     * @param string $operation
     */
    public function testIsNotMathOperation($operation)
    {
        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $left */
        $left = $this->getMock(NodeInterface::class);
        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $right */
        $right = $this->getMock(NodeInterface::class);

        $node = new BinaryNode($left, $right, $operation);
        $this->assertFalse($node->isMathOperation());
    }

    /**
     * @dataProvider mathExpressionsDataProvider
     * @param string $operation
     */
    public function testIsMathOperation($operation)
    {
        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $left */
        $left = $this->getMock(NodeInterface::class);
        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $right */
        $right = $this->getMock(NodeInterface::class);

        $node = new BinaryNode($left, $right, $operation);
        $this->assertTrue($node->isMathOperation());
    }

    /**
     * @return array
     */
    public function mathExpressionsDataProvider()
    {
        return [
            ['+'],
            ['-'],
            ['*'],
            ['/'],
            ['%'],
        ];
    }
}
