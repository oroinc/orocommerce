<?php

namespace Oro\Bundle\RedirectBundle\EventListener;

use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Helper\SlugifyEntityHelper;

/**
 * Sets slug to entity if source exists and slug empty.
 */
class ImportSluggableEntityListener
{
    /**
     * @var SlugifyEntityHelper
     */
    private $slugifyEntityHelper;

    /**
     * @param SlugifyEntityHelper $slugifyEntityHelper
     */
    public function __construct(SlugifyEntityHelper $slugifyEntityHelper)
    {
        $this->slugifyEntityHelper = $slugifyEntityHelper;
    }

    /**
     * @param StrategyEvent $event
     */
    public function onProcessBefore(StrategyEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof SluggableInterface) {
            $this->slugifyEntityHelper->fill($entity);
        }
    }
}
