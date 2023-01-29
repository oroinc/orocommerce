<?php

namespace Oro\Bundle\CMSBundle\Entity\EntityListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;

/**
 * Handles ImageSlide entity deletion and deletes related images.
 */
class ImageSlideEntityListener
{
    public function preRemove(ImageSlide $imageSlide, LifecycleEventArgs $args): void
    {
        $manager = $args->getObjectManager();

        if ($imageSlide->getMainImage()) {
            $manager->remove($imageSlide->getMainImage());
        }

        if ($imageSlide->getMediumImage()) {
            $manager->remove($imageSlide->getMediumImage());
        }

        if ($imageSlide->getSmallImage()) {
            $manager->remove($imageSlide->getSmallImage());
        }
    }
}
