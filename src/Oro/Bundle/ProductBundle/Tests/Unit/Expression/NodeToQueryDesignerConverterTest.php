<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Expression;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Expression\NodeToQueryDesignerConverter;
use Oro\Bundle\ProductBundle\Model\NodeExpressionQueryDesigner;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\ColumnInformationProviderInterface;

class NodeToQueryDesignerConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NodeToQueryDesignerConverter
     */
    protected $converter;

    protected function setUp()
    {
        $this->converter = new NodeToQueryDesignerConverter(Product::class);
    }

    public function testConvert()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|NodeInterface $subNode */
        $subNode = $this->getMock(NodeInterface::class);
        /** @var \PHPUnit_Framework_MockObject_MockObject|NodeInterface $node */
        $node = $this->getMock(NodeInterface::class);
        $node->expects($this->once())
            ->method('getNodes')
            ->willReturn([$subNode]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ColumnInformationProviderInterface $columnInfoProvider */
        $columnInfoProvider = $this->getMock(ColumnInformationProviderInterface::class);
        $columnInfoProvider->expects($this->once())
            ->method('fillColumnInformation')
            ->willReturnCallback(
                function ($subNode, array  &$addedColumns, array &$definition) {
                    $definition['columns'][] = ['name' => 'test'];
                    return true;
                }
            );

        $expectedDefinition = [
            'columns' => [['name' => 'test']]
        ];
        $this->converter->addColumnInformationProvider($columnInfoProvider);

        $source = $this->converter->convert($node);
        $this->assertInstanceOf(NodeExpressionQueryDesigner::class, $source);
        $this->assertEquals(Product::class, $source->getEntity());
        $this->assertJsonStringEqualsJsonString(json_encode($expectedDefinition), $source->getDefinition());
    }
}
