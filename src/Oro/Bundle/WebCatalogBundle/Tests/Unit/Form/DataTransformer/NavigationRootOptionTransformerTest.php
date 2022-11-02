<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Form\DataTransformer\NavigationRootOptionTransformer;
use Oro\Component\Testing\Unit\EntityTrait;

class NavigationRootOptionTransformerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var NavigationRootOptionTransformer */
    private $transformer;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->transformer = new NavigationRootOptionTransformer($this->doctrineHelper);
    }

    public function testTransform()
    {
        $repository = $this->createMock(ObjectRepository::class);
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(ContentNode::class)
            ->willReturn($repository);

        $contentNode = new ContentNode();
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($contentNode);

        $this->assertSame($contentNode, $this->transformer->transform(1));
    }

    public function testReverseTransform()
    {
        $contentNode = $this->getEntity(ContentNode::class, ['id' => 777]);

        $this->assertSame(777, $this->transformer->reverseTransform($contentNode));
    }

    public function testReverseTransformIdPassed()
    {
        $this->assertSame(777, $this->transformer->reverseTransform(777));
    }
}
