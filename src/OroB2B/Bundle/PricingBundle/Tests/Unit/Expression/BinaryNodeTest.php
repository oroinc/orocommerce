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

    /**
     * @dataProvider booleanOperationsDataProvider
     * @param string $operation
     */
    public function testIsBoolean($operation)
    {
        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $left */
        $left = $this->getMock(NodeInterface::class);
        $left->expects($this->once())
            ->method('isBoolean')
            ->willReturn(true);
        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $right */
        $right = $this->getMock(NodeInterface::class);
        $right->expects($this->once())
            ->method('isBoolean')
            ->willReturn(true);
        $node = new BinaryNode($left, $right, $operation);
        $this->assertTrue($node->isBoolean());
    }

    /**
     * @return array
     */
    public function booleanOperationsDataProvider()
    {
        return [['and'], ['or']];
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

    public function testIsBooleanFalseForMathExpression()
    {
        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $left */
        $left = $this->getMock(NodeInterface::class);
        $left->expects($this->never())
            ->method('isBoolean');
        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $right */
        $right = $this->getMock(NodeInterface::class);
        $right->expects($this->never())
            ->method('isBoolean');

        $node = new BinaryNode($left, $right, '+');
        $this->assertFalse($node->isBoolean());
    }

    /**
     * @dataProvider leftRightDataProvider
     * @param string $operation
     * @param bool $leftIsBoolean
     * @param bool $rightIsBoolean
     */
    public function testIsBooleanFalse($operation, $leftIsBoolean, $rightIsBoolean)
    {
        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $left */
        $left = $this->getMock(NodeInterface::class);
        $left->expects($this->any())
            ->method('isBoolean')
            ->willReturn($leftIsBoolean);
        /** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $right */
        $right = $this->getMock(NodeInterface::class);
        $right->expects($this->any())
            ->method('isBoolean')
            ->willReturn($rightIsBoolean);
        $node = new BinaryNode($left, $right, $operation);
        $this->assertFalse($node->isBoolean());
    }

    /**
     * @return array
     */
    public function leftRightDataProvider()
    {
        $operations = $this->booleanOperationsDataProvider();
        $checkData = [[true, false], [false, true], [false, false]];
        $data = [];
        foreach ($operations as $operation) {
            foreach ($checkData as $dataToCheck) {
                $data[] = array_merge($operation, $dataToCheck);
            }
        }

        return $data;
    }
}
