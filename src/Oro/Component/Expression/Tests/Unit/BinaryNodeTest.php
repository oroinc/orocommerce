<?php

namespace Oro\Component\Expression\Tests\Unit;

use Oro\Component\Expression\Node\BinaryNode;
use Oro\Component\Expression\Node\NodeInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class BinaryNodeTest extends \PHPUnit\Framework\TestCase
{
    public function testNode()
    {
        $left = $this->createMock(NodeInterface::class);
        $left->expects($this->any())
            ->method('getNodes')
            ->willReturn([$left]);

        $right = $this->createMock(NodeInterface::class);
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
     */
    public function testIsBooleanOperation(string $operation)
    {
        $left = $this->createMock(NodeInterface::class);
        $left->expects($this->never())
            ->method('isBoolean');
        $right = $this->createMock(NodeInterface::class);
        $right->expects($this->never())
            ->method('isBoolean');
        $node = new BinaryNode($left, $right, $operation);
        $this->assertTrue($node->isBoolean());
    }

    public function booleanOperationsDataProvider(): array
    {
        return [
            ['and'],
            ['or']
        ];
    }

    /**
     * @dataProvider booleanExpressionsDataProvider
     */
    public function testIsBooleanExpression(string $operation)
    {
        $left = $this->createMock(NodeInterface::class);
        $left->expects($this->never())
            ->method('isBoolean');
        $right = $this->createMock(NodeInterface::class);
        $right->expects($this->never())
            ->method('isBoolean');

        $node = new BinaryNode($left, $right, $operation);
        $this->assertTrue($node->isBoolean());
    }

    public function booleanExpressionsDataProvider(): array
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
     */
    public function testIsBooleanForMathExpression(bool $leftBoolean, bool $rightBoolean, bool $expected)
    {
        $left = $this->createMock(NodeInterface::class);
        $left->expects($this->any())
            ->method('isBoolean')
            ->willReturn($leftBoolean);
        $right = $this->createMock(NodeInterface::class);
        $right->expects($this->any())
            ->method('isBoolean')
            ->willReturn($rightBoolean);

        $node = new BinaryNode($left, $right, '+');
        $this->assertEquals($expected, $node->isBoolean());
    }

    public function leftRightBooleanDataProvider(): array
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
     */
    public function testIsNotMathOperation(string $operation)
    {
        $left = $this->createMock(NodeInterface::class);
        $right = $this->createMock(NodeInterface::class);

        $node = new BinaryNode($left, $right, $operation);
        $this->assertFalse($node->isMathOperation());
    }

    /**
     * @dataProvider mathExpressionsDataProvider
     */
    public function testIsMathOperation(string $operation)
    {
        $left = $this->createMock(NodeInterface::class);
        $right = $this->createMock(NodeInterface::class);

        $node = new BinaryNode($left, $right, $operation);
        $this->assertTrue($node->isMathOperation());
    }

    public function mathExpressionsDataProvider(): array
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
