<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CommerceEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator;
use Oro\Bundle\WebCatalogBundle\Model\ContentNodeMaterializedPathModifier;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\EventListener\ContentNodeListener;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentNodeListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ContentNodeMaterializedPathModifier|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $modifier;

    /**
     * @var ExtraActionEntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storage;

    /**
     * @var SlugGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $slugGenerator;

    /**
     * @var ContentNodeListener
     */
    protected $contentNodeListener;

    protected function setUp()
    {
        $this->modifier = $this->getMockBuilder(ContentNodeMaterializedPathModifier::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storage = $this->createMock(ExtraActionEntityStorageInterface::class);

        $this->slugGenerator = $this->getMockBuilder(SlugGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $generatorLink = $this->getMockBuilder(ServiceLink::class)
            ->disableOriginalConstructor()
            ->getMock();
        $generatorLink->expects($this->any())
            ->method('getService')
            ->willReturn($this->slugGenerator);

        $this->contentNodeListener = new ContentNodeListener(
            $this->modifier,
            $this->storage,
            $generatorLink
        );
    }

    public function testPostPersist()
    {
        $contentNode = new ContentNode();

        $this->modifier->expects($this->once())
            ->method('calculateMaterializedPath')
            ->with($contentNode);

        $this->modifier->expects($this->once())
            ->method('calculateMaterializedPath')
            ->with($contentNode);

        $this->contentNodeListener->postPersist($contentNode);
    }

    public function testPreUpdate()
    {
        /** @var ContentNode $contentNode **/
        $contentNode = $this->getEntity(ContentNode::class, ['id' => 42]);

        /** @var PreUpdateEventArgs|\PHPUnit_Framework_MockObject_MockObject $args **/
        $args = $this->getMockBuilder(PreUpdateEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $args->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn(
                [
                    ContentNode::FIELD_PARENT_NODE => [
                        null,
                        new ContentNode()
                    ]
                ]
            );

        $childNode = new ContentNode();

        $this->modifier->expects($this->once())
            ->method('calculateChildrenMaterializedPath')
            ->with($contentNode)
            ->willReturn([new ContentNode()]);

        $this->storage->expects($this->at(0))
            ->method('scheduleForExtraInsert')
            ->with($contentNode);

        $this->storage->expects($this->at(1))
            ->method('scheduleForExtraInsert')
            ->with($childNode);

        $this->slugGenerator->expects($this->once())
            ->method('generate')
            ->with($contentNode);

        $this->contentNodeListener->preUpdate($contentNode, $args);
    }
}
