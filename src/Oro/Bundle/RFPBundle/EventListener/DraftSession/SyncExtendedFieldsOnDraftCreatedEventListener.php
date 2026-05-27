<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\EventListener\DraftSession;

use Oro\Component\DraftSession\Event\EntityDraftCreatedEvent;
use Oro\Component\DraftSession\ExtendedFields\EntityDraftExtendedFieldsProvider;
use Oro\Component\DraftSession\ExtendedFields\EntityDraftExtendedFieldSynchronizer;

/**
 * Synchronizes extended fields from a source entity to a draft entity.
 */
class SyncExtendedFieldsOnDraftCreatedEventListener
{
    public function __construct(
        private readonly EntityDraftExtendedFieldsProvider $extendedFieldsProvider,
        private readonly EntityDraftExtendedFieldSynchronizer $extendedFieldSynchronizer,
        private readonly string $sourceEntityClass,
        private readonly string $targetEntityClass,
    ) {
    }

    public function onEntityDraftCreated(EntityDraftCreatedEvent $event): void
    {
        $source = $event->getEntity();
        $target = $event->getDraft();

        if (!is_a($source, $this->sourceEntityClass) || !is_a($target, $this->targetEntityClass)) {
            return;
        }

        $applicableExtendedFields = array_intersect_key(
            $this->extendedFieldsProvider->getApplicableExtendedFields($this->targetEntityClass),
            $this->extendedFieldsProvider->getApplicableExtendedFields($this->sourceEntityClass),
        );

        foreach ($applicableExtendedFields as $fieldName => $fieldType) {
            $this->extendedFieldSynchronizer->synchronize($source, $target, $fieldName, $fieldType);
        }
    }
}
