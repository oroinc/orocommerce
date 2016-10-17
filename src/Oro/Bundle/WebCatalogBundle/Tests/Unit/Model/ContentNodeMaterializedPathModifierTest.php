<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Model;

use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Model\ContentNodeMaterializedPathModifier;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentNodeMaterializedPathModifierTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ExtraActionEntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storage;

    /**
     * @var ContentNodeMaterializedPathModifier
     */
    protected $modifier;

    protected function setUp()
    {
        $this->storage = $this->getMock(ExtraActionEntityStorageInterface::class);
        $this->modifier = new ContentNodeMaterializedPathModifier($this->storage);
    }

    public function testCalculateMaterializedPathWithScheduleForInsert()
    {
        $parentNode = new ContentNode();
        $parentNode->setMaterializedPath('1_2');

        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => 3]);
        $node->setParentNode($parentNode);

        $this->storage->expects($this->once())
            ->method('scheduleForExtraInsert')
            ->with($node);

        $this->modifier->calculateMaterializedPath($node, true);
        $this->assertEquals('1_2_3', $node->getMaterializedPath());
    }
    
    public function testCalculateMaterializedPathWithoutScheduleForInsert()
    {
        $parentNode = new ContentNode();
        $parentNode->setMaterializedPath('1_2');

        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => 3]);
        $node->setParentNode($parentNode);

        $this->storage->expects($this->never())
            ->method('scheduleForExtraInsert');

        $this->modifier->calculateMaterializedPath($node, false);
        $this->assertEquals('1_2_3', $node->getMaterializedPath());
    }

    public function testUpdateMaterializedPathNested()
    {
        /** @var ContentNode $parentNode */
        $parentNode = $this->getEntity(ContentNode::class, ['id' => 1]);

        /** @var ContentNode $node */
        $node = $this->getEntity(ContentNode::class, ['id' => 2]);
        $node->setParentNode($parentNode);

        $children = [$node];
        
        $this->storage->expects($this->once())
            ->method('scheduleForExtraInsert');

        $this->modifier->updateMaterializedPathNested($parentNode, $children);

        $this->assertEquals('1', $parentNode->getMaterializedPath());
        $this->assertEquals('1_2', $node->getMaterializedPath());
    }
}
