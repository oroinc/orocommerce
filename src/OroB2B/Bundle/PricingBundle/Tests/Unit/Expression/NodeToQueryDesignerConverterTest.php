<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Expression;

use OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Expression\NameNode;
use OroB2B\Bundle\PricingBundle\Expression\NodeInterface;
use OroB2B\Bundle\PricingBundle\Expression\NodeToQueryDesignerConverter;
use OroB2B\Bundle\PricingBundle\Expression\RelationNode;
use OroB2B\Bundle\PricingBundle\Model\PriceListQueryDesigner;
use OroB2B\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class NodeToQueryDesignerConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceRuleFieldsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldsProvider;

    /**
     * @var NodeToQueryDesignerConverter
     */
    protected $converter;

    protected function setUp()
    {
        $this->fieldsProvider = $this->getMockBuilder(PriceRuleFieldsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->converter = new NodeToQueryDesignerConverter($this->fieldsProvider);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unsupported field stdClass::field
     */
    public function testConvertUnsupportedName()
    {
        $node = new NameNode(\stdClass::class, 'field');
        $this->converter->convert($node);
    }

    /**
     * @dataProvider nodeDataProvider
     * @param NodeInterface $node
     * @param array $expectedDefinition
     */
    public function testConvertName($node, array $expectedDefinition)
    {
        $definition = new PriceListQueryDesigner();
        $definition->setEntity(Product::class);
        $definition->setDefinition(json_encode($expectedDefinition));
        $this->assertEquals($definition, $this->converter->convert($node));
    }

    /**
     * @return array
     */
    public function nodeDataProvider()
    {
        return [
            'product field' => [
                new NameNode(Product::class, 'id'),
                [
                    'columns' => [
                        [
                            'name' => 'id',
                            'table_identifier' => Product::class
                        ]
                    ]
                ]
            ],
            'product price field' => [
                new NameNode(ProductPrice::class, 'currency'),
                [
                    'columns' => [
                        [
                            'name' => sprintf('%1$s::product+%1$s::%2$s', ProductPrice::class, 'currency'),
                            'table_identifier' => ProductPrice::class
                        ]
                    ]
                ]
            ]
        ];
    }

    public function testConvertPriceAttribute()
    {
        $node = new RelationNode(Product::class, 'msrp', 'currency');
        $this->fieldsProvider->expects($this->once())
            ->method('getRealClassName')
            ->with($node->getRelationAlias())
            ->willReturn(PriceAttributeProductPrice::class);

        $expectedDefinition = [
            'columns' => [
                [
                    'name' => sprintf('%s+%s::%s', 'msrp', PriceAttributeProductPrice::class, 'currency'),
                    'table_identifier' => $node->getRelationAlias()
                ]
            ]
        ];

        $definition = new PriceListQueryDesigner();
        $definition->setEntity(Product::class);
        $definition->setDefinition(json_encode($expectedDefinition));
        $this->assertEquals($definition, $this->converter->convert($node));
    }
}
