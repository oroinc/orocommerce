<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CMSBundle\Entity\EntityListener\ImageSlideEntityListener;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\ImageSlide;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImageSlideEntityListenerTest extends TestCase
{
    private ImageSlideEntityListener $listener;
    private EntityManagerInterface|MockObject $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->listener = new ImageSlideEntityListener();
    }

    public function testPreRemove(): void
    {
        $extraLargeImage = $this->getFile(1001);
        $extraLargeImage2x = $this->getFile(1002);
        $extraLargeImage3x = $this->getFile(1003);

        $largeImage = $this->getFile(2001);
        $largeImage2x = $this->getFile(2002);
        $largeImage3x = $this->getFile(2003);

        $mediumImage = $this->getFile(3001);
        $mediumImage2x = $this->getFile(3002);
        $mediumImage3x = $this->getFile(3003);

        $smallImage = $this->getFile(4001);
        $smallImage2x = $this->getFile(4002);
        $smallImage3x = $this->getFile(4003);

        $imageSlide = new ImageSlide();
        $imageSlide->setExtraLargeImage($extraLargeImage)
            ->setExtraLargeImage2x($extraLargeImage2x)
            ->setExtraLargeImage3x($extraLargeImage3x)
            ->setLargeImage($largeImage)
            ->setLargeImage2x($largeImage2x)
            ->setLargeImage3x($largeImage3x)
            ->setMediumImage($mediumImage)
            ->setMediumImage2x($mediumImage2x)
            ->setMediumImage3x($mediumImage3x)
            ->setSmallImage($smallImage)
            ->setSmallImage2x($smallImage2x)
            ->setSmallImage3x($smallImage3x);

        $this->entityManager->expects($this->exactly(12))
            ->method('remove')
            ->withConsecutive(
                [$extraLargeImage],
                [$extraLargeImage2x],
                [$extraLargeImage3x],
                [$largeImage],
                [$largeImage2x],
                [$largeImage3x],
                [$mediumImage],
                [$mediumImage2x],
                [$mediumImage3x],
                [$smallImage],
                [$smallImage2x],
                [$smallImage3x]
            );

        $this->listener->preRemove($imageSlide, new LifecycleEventArgs($imageSlide, $this->entityManager));
    }

    public function testPreRemoveWithEmptyImages(): void
    {
        $imageSlide = new ImageSlide();

        $this->entityManager->expects($this->never())
            ->method('remove');

        $this->listener->preRemove($imageSlide, new LifecycleEventArgs($imageSlide, $this->entityManager));
    }

    private function getFile(int $id): File
    {
        $file = new File();
        ReflectionUtil::setId($file, $id);

        return $file;
    }
}
