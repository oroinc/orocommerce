<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Expression\ColumnInformation;

use Oro\Bundle\ProductBundle\Expression\ColumnInformation\DefaultProvider;
use Oro\Component\Expression\FieldsProviderInterface;
use Oro\Component\Expression\Node\NameNode;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\Node\RelationNode;

class DefaultProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FieldsProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldsProvider;

    /**
     * @var DefaultProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->fieldsProvider = $this->getMock(FieldsProviderInterface::class);
        $this->provider = new DefaultProvider($this->fieldsProvider);
    }

    public function testFillColumnInformationNotSupportedNode()
    {
        $addedColumns = [];
        $definition = [];
        $node = $this->getMock(NodeInterface::class);

        $this->assertFalse($this->provider->fillColumnInformation($node, $addedColumns, $definition));
        $this->assertEquals([], $definition);
        $this->assertEquals([], $addedColumns);
    }

    public function testFillColumnInformationNameNode()
    {
        $addedColumns = [];
        $definition = [];
        $node = new NameNode('a', 'b');

        $this->assertTrue($this->provider->fillColumnInformation($node, $addedColumns, $definition));
        $this->assertEquals(['columns' => [['name' => 'b', 'table_identifier' => 'a']]], $definition);
        $this->assertEquals(['b' => true], $addedColumns);
    }

    public function testFillColumnInformationRelationNode()
    {
        $addedColumns = [];
        $definition = [];
        $node = new RelationNode('a', 'b', 'c');

        $this->fieldsProvider->expects($this->once())
            ->method('getRealClassName')
            ->with('a::b')
            ->willReturn('ABClass');

        $this->assertTrue($this->provider->fillColumnInformation($node, $addedColumns, $definition));
        $this->assertEquals(['columns' => [['name' => 'b+ABClass::c', 'table_identifier' => 'a::b']]], $definition);
        $this->assertEquals(['b+ABClass::c' => true], $addedColumns);
    }
}
