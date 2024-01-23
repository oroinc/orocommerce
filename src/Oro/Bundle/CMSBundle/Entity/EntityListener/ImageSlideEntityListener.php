<?php

namespace Oro\Bundle\CMSBundle\Entity\EntityListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;

/**
 * Handles ImageSlide entity deletion and deletes related images.
 */
class ImageSlideEntityListener
{
    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function preRemove(ImageSlide $imageSlide, LifecycleEventArgs $args): void
    {
        $manager = $args->getObjectManager();

        if ($imageSlide->getExtraLargeImage()) {
            $manager->remove($imageSlide->getExtraLargeImage());
        }
        if ($imageSlide->getExtraLargeImage2x()) {
            $manager->remove($imageSlide->getExtraLargeImage2x());
        }
        if ($imageSlide->getExtraLargeImage3x()) {
            $manager->remove($imageSlide->getExtraLargeImage3x());
        }

        if ($imageSlide->getLargeImage()) {
            $manager->remove($imageSlide->getLargeImage());
        }
        if ($imageSlide->getLargeImage2x()) {
            $manager->remove($imageSlide->getLargeImage2x());
        }
        if ($imageSlide->getLargeImage3x()) {
            $manager->remove($imageSlide->getLargeImage3x());
        }

        if ($imageSlide->getMediumImage()) {
            $manager->remove($imageSlide->getMediumImage());
        }
        if ($imageSlide->getMediumImage2x()) {
            $manager->remove($imageSlide->getMediumImage2x());
        }
        if ($imageSlide->getMediumImage3x()) {
            $manager->remove($imageSlide->getMediumImage3x());
        }

        if ($imageSlide->getSmallImage()) {
            $manager->remove($imageSlide->getSmallImage());
        }
        if ($imageSlide->getSmallImage2x()) {
            $manager->remove($imageSlide->getSmallImage2x());
        }
        if ($imageSlide->getSmallImage3x()) {
            $manager->remove($imageSlide->getSmallImage3x());
        }
    }
}
