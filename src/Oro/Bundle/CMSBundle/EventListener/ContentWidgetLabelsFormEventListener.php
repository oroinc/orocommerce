<?php

namespace Oro\Bundle\CMSBundle\EventListener;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

/**
 * Handles adding and removing the Labels collection on content widget form.
 */
class ContentWidgetLabelsFormEventListener
{
    public function __construct(
        private ManagerRegistry $registry
    ) {
    }

    public function onBeforeFlush(AfterFormProcessEvent $args): void
    {
        /** @var ContentWidget $contentWidget */
        $contentWidget = $args->getData();
        if (!$this->isApplicable($contentWidget)) {
            return;
        }

        $settings = $contentWidget->getSettings();
        $labels = $settings['labels']->toArray();

        $manager = $this->registry->getManagerForClass(LocalizedFallbackValue::class);
        foreach ($labels as $label) {
            $contentWidget->addLabel($label);
            $manager->persist($label);
        }

        unset($settings['labels']);
        $contentWidget->setSettings($settings);
    }

    private function isApplicable(mixed $contentWidget): bool
    {
        return $contentWidget instanceof ContentWidget &&
            isset($contentWidget->getSettings()['labels']) &&
            $contentWidget->getSettings()['labels'] instanceof Collection;
    }
}
