<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\DraftSession\Manager;

use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Manager\EntityDraftManager;
use Oro\Component\DraftSession\Provider\DraftSessionUuidProvider;

/**
 * Provides methods to manage order drafts in the context of a draft session.
 */
class OrderDraftManager
{
    public function __construct(
        private readonly DraftSessionUuidProvider $draftSessionUuidProvider,
        private readonly EntityDraftManager $entityDraftManager
    ) {
    }

    public function getDraftSessionUuid(): ?string
    {
        return $this->draftSessionUuidProvider->getDraftSessionUuid();
    }

    /**
     * Determines whether a draft exists for the given entity in the resolved draft session.
     *
     * @param EntityDraftAwareInterface $entity Entity to check draft presence for.
     * @param string|null $draftSessionUuid Draft session UUID; current session UUID is used when null.
     *
     * @return bool True when a matching draft exists; otherwise false.
     */
    public function hasEntityDraft(
        EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid = null
    ): bool {
        return $this->entityDraftManager->hasEntityDraft($entity, $draftSessionUuid);
    }

    /**
     * Finds a draft for the given entity in the resolved draft session.
     *
     * @param EntityDraftAwareInterface $entity Entity to find a draft for.
     * @param string|null $draftSessionUuid Draft session UUID; current session UUID is used when null.
     *
     * @return EntityDraftAwareInterface|null Draft entity when found; otherwise null.
     */
    public function findEntityDraft(
        EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid = null
    ): ?EntityDraftAwareInterface {
        return $this->entityDraftManager->findEntityDraft($entity, $draftSessionUuid);
    }

    /**
     * Loads entity state from its draft using loader service logic.
     *
     * @param EntityDraftAwareInterface $entity Regular entity or draft entity.
     * @param string|null $draftSessionUuid Draft session UUID; current session UUID is used when null.
     *
     * @return EntityDraftAwareInterface Synchronized regular entity instance.
     */
    public function loadFromEntityDraft(
        EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid = null
    ): EntityDraftAwareInterface {
        return $this->entityDraftManager->loadFromEntityDraft($entity, $draftSessionUuid);
    }

    /**
     * Saves draft state for the given entity using persister service logic.
     *
     * @param EntityDraftAwareInterface $entity Regular entity or draft entity.
     * @param string|null $draftSessionUuid Draft session UUID; current session UUID is used when null.
     *
     * @return EntityDraftAwareInterface Persisted draft entity.
     */
    public function saveToEntityDraft(
        EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid = null
    ): EntityDraftAwareInterface {
        return $this->entityDraftManager->saveToEntityDraft($entity, $draftSessionUuid);
    }

    /**
     * Deletes a draft for the given entity in the resolved draft session.
     *
     * @param EntityDraftAwareInterface $entity Entity whose draft should be removed.
     * @param string|null $draftSessionUuid Draft session UUID; current session UUID is used when null.
     */
    public function deleteEntityDraft(
        EntityDraftAwareInterface $entity,
        ?string $draftSessionUuid = null
    ): void {
        $this->entityDraftManager->deleteEntityDraft($entity, $draftSessionUuid);
    }
}
