<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Expression;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Expression\NodeToQueryDesignerConverter;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Component\Expression\ColumnInformationProviderInterface;
use Oro\Component\Expression\Node\NodeInterface;

class NodeToQueryDesignerConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var NodeToQueryDesignerConverter */
    private $converter;

    protected function setUp(): void
    {
        $this->converter = new NodeToQueryDesignerConverter();
    }

    public function testConvert()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|NodeInterface $subNode */
        $subNode = $this->createMock(NodeInterface::class);
        /** @var \PHPUnit\Framework\MockObject\MockObject|NodeInterface $node */
        $node = $this->createMock(NodeInterface::class);
        $node->expects($this->once())
            ->method('getNodes')
            ->willReturn([$subNode]);

        /** @var \PHPUnit\Framework\MockObject\MockObject|ColumnInformationProviderInterface $columnInfoProvider */
        $columnInfoProvider = $this->createMock(ColumnInformationProviderInterface::class);
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
        $this->assertEquals(Product::class, $source->getEntity());
        $this->assertJsonStringEqualsJsonString(
            QueryDefinitionUtil::encodeDefinition($expectedDefinition),
            $source->getDefinition()
        );
    }
}
