<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Generator;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\UniqueSlugResolver;
use Oro\Component\Testing\Unit\EntityTrait;

class UniqueSlugResolverTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var UniqueSlugResolver
     */
    protected $uniqueSlugResolver;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->uniqueSlugResolver = new UniqueSlugResolver($this->registry);
    }

    public function testResolveNewSlug()
    {
        $slug = '/test';
        $slugUrl = new SlugUrl($slug);

        /** @var SluggableInterface|\PHPUnit_Framework_MockObject_MockObject $entity **/
        $entity = $this->createMock(SluggableInterface::class);

        $repository = $this->createMock(SlugRepository::class);
        $repository->expects($this->once())
            ->method('findOneBySlug')
            ->with($slug)
            ->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

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

        $repository = $this->createMock(SlugRepository::class);
        $repository->expects($this->once())
            ->method('findOneBySlug')
            ->with($slug)
            ->willReturn(new Slug());

        $repository->expects($this->once())
            ->method('findAllSlugByPattern')
            ->with('/test-%', $entity)
            ->willReturn([$existingSlug]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

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

        $repository = $this->createMock(SlugRepository::class);
        $repository->expects($this->any())
            ->method('findOneBySlug')
            ->willReturnMap([
                [$slug, $entity, $this->getEntity(Slug::class, ['id' => 123])],
                ['/test', $entity, $this->getEntity(Slug::class, ['id' => 42])]
            ]);

        $repository->expects($this->once())
            ->method('findAllSlugByPattern')
            ->with('/test-%', $entity)
            ->willReturn([$existingSlug]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->assertEquals($expectedSlug, $this->uniqueSlugResolver->resolve($slugUrl, $entity));
    }
}
