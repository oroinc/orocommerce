<?php

namespace Oro\Bundle\CMSBundle\EventListener;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\ContentWidget\ImageSliderContentWidgetType;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;

/**
 * Handles adding and removing the ImageSlide entities on content widget form.
 */
class ContentWidgetFormEventListener
{
    /** @var ManagerRegistry */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function onBeforeFlush(AfterFormProcessEvent $args): void
    {
        $contentWidget = $args->getData();
        if (!$contentWidget instanceof ContentWidget ||
            $contentWidget->getWidgetType() !== ImageSliderContentWidgetType::getName()
        ) {
            return;
        }

        $settings = $contentWidget->getSettings();

        /** @var Collection|ImageSlide[] $newImageSlides */
        $newImageSlides = $settings['imageSlides'] ?? [];

        $manager = $this->registry->getManagerForClass(ImageSlide::class);
        foreach ($newImageSlides as $imageSlide) {
            $manager->persist($imageSlide);
        }

        $oldImageSlides = $manager->getRepository(ImageSlide::class)
            ->findBy(['contentWidget' => $contentWidget]);

        $toRemove = array_udiff(
            $oldImageSlides,
            $newImageSlides->toArray(),
            static function (ImageSlide $a, ImageSlide $b) {
                return $a->getId() <=> $b->getId();
            }
        );

        /** @var ImageSlide $imageSlide */
        foreach ($toRemove as $imageSlide) {
            $manager->remove($imageSlide);
        }

        unset($settings['imageSlides']);

        $contentWidget->setSettings($settings);
    }
}
