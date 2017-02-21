<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Generator;

use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl;
use Oro\Bundle\RedirectBundle\Generator\UniqueSlugResolver;
use Oro\Component\Testing\Unit\EntityTrait;

class UniqueSlugResolverTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var SlugRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var UniqueSlugResolver
     */
    protected $uniqueSlugResolver;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder(SlugRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->uniqueSlugResolver = new UniqueSlugResolver($this->repository);
    }

    public function testResolveNewSlug()
    {
        $slug = '/test';
        $slugUrl = new SlugUrl($slug);

        /** @var SluggableInterface|\PHPUnit_Framework_MockObject_MockObject $entity **/
        $entity = $this->createMock(SluggableInterface::class);

        $this->repository->expects($this->once())
            ->method('findOneDirectUrlBySlug')
            ->with($slug, $entity)
            ->willReturn(null);

        $this->assertEquals($slug, $this->uniqueSlugResolver->resolve($slugUrl, $entity));
    }

    public function testResolveExistingSlug()
    {
        $slug = '/test';
        $existingSlug = '/test-1';
        $expectedSlug = '/test-2';

        $slugUrl = new SlugUrl($slug);

        /** @var SluggableInterface|\PHPUnit_Framework_MockObject_MockObject $entity **/
        $entity = $this->createMock(SluggableInterface::class);

        $this->repository->expects($this->once())
            ->method('findOneDirectUrlBySlug')
            ->with($slug, $entity)
            ->willReturn(new Slug());

        $this->repository->expects($this->once())
            ->method('findAllDirectUrlsByPattern')
            ->with('/test-%', $entity)
            ->willReturn([$existingSlug]);

        $this->assertEquals($expectedSlug, $this->uniqueSlugResolver->resolve($slugUrl, $entity));
    }

    public function testResolveExistingIncrementedSlug()
    {
        $slug = '/test-1';
        $existingSlug = '/test-1';
        $expectedSlug = '/test-2';

        $slugUrl = new SlugUrl($slug);

        /** @var SluggableInterface|\PHPUnit_Framework_MockObject_MockObject $entity **/
        $entity = $this->createMock(SluggableInterface::class);

        $this->repository->expects($this->any())
            ->method('findOneDirectUrlBySlug')
            ->willReturnMap([
                [$slug, $entity, null, $this->getEntity(Slug::class, ['id' => 123])],
                ['/test', $entity, null, $this->getEntity(Slug::class, ['id' => 42])]
            ]);

        $this->repository->expects($this->once())
            ->method('findAllDirectUrlsByPattern')
            ->with('/test-%', $entity)
            ->willReturn([$existingSlug]);

        $this->assertEquals($expectedSlug, $this->uniqueSlugResolver->resolve($slugUrl, $entity));
    }
}
