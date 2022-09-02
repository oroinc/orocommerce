<?php

namespace Oro\Bundle\CMSBundle\EventListener;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\ContentWidget\TabbedContentWidgetType;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\TabbedContentItem;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;

/**
 * Handles adding and removing of the TabbedContentItem entities on the content widget form.
 */
class TabbedContentWidgetFormEventListener
{
    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Processes and persists the collection of TabbedContentItem coming from the corresponding content widget.
     *
     * @param AfterFormProcessEvent $args
     */
    public function onBeforeFlush(AfterFormProcessEvent $args): void
    {
        $contentWidget = $args->getData();
        if (!$contentWidget instanceof ContentWidget ||
            $contentWidget->getWidgetType() !== TabbedContentWidgetType::getName()
        ) {
            return;
        }

        $settings = $contentWidget->getSettings();

        if ($settings['tabbedContentItems']) {
            /** @var TabbedContentItem[] $newItems */
            $newItems =
                $settings['tabbedContentItems'] instanceof Collection ?
                    $settings['tabbedContentItems']->toArray() :
                    $settings['tabbedContentItems'];
        } else {
            /** @var TabbedContentItem[] $newItems */
            $newItems = [];
        }

        $entityManager = $this->managerRegistry->getManagerForClass(TabbedContentItem::class);
        foreach ($newItems as $item) {
            $entityManager->persist($item);
        }

        $oldItems = $entityManager->getRepository(TabbedContentItem::class)
            ->findBy(['contentWidget' => $contentWidget]);

        $toRemove = array_udiff(
            $oldItems,
            $newItems,
            static function (TabbedContentItem $a, TabbedContentItem $b) {
                return $a->getId() <=> $b->getId();
            }
        );

        foreach ($toRemove as $item) {
            $entityManager->remove($item);
        }

        unset($settings['tabbedContentItems']);

        $contentWidget->setSettings($settings);
    }
}
