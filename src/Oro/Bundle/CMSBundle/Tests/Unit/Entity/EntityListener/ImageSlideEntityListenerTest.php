<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CMSBundle\Entity\EntityListener\ImageSlideEntityListener;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\ImageSlide;

class ImageSlideEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImageSlideEntityListener */
    private $listener;

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->listener = new ImageSlideEntityListener();
    }

    public function testPreRemove(): void
    {
        $imageSlide = new ImageSlide();
        $imageSlide->setMainImage(new File());
        $imageSlide->setMediumImage(new File());
        $imageSlide->setSmallImage(new File());

        $this->entityManager->expects($this->exactly(3))
            ->method('remove')
            ->with(new File());

        $this->listener->preRemove($imageSlide, new LifecycleEventArgs($imageSlide, $this->entityManager));
    }

    public function testPreRemoveWithEmptyImages(): void
    {
        $imageSlide = new ImageSlide();

        $this->entityManager->expects($this->never())
            ->method('remove');

        $this->listener->preRemove($imageSlide, new LifecycleEventArgs($imageSlide, $this->entityManager));
    }
}
