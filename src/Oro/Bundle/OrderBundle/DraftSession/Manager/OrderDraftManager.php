<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\DraftSession\Manager;

use Doctrine\Common\Util\ClassUtils;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Manager\EntityDraftManager;
use Oro\Component\DraftSession\Provider\DraftSessionUuidProvider;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Provides methods to manage order drafts in the context of a draft session.
 */
class OrderDraftManager implements ResetInterface
{
    /**
     * Local cache of entities loaded from drafts. Cleared via `::reset()` method.
     *
     * @var array<string, array<string, array<int, EntityDraftAwareInterface>>>
     *   [
     *     '32fded2f-c76f-455b-aaf7-2c440c8d12d3' => [ // Draft session UUID
     *       'Oro\Bundle\OrderBundle\Entity\Order' => [ // Draft entity FQCN
     *         '00000000000000150000000000000000' => Order, // SPL object hash => Entity synced from its draft
     *       ],
     *     ],
     *   ]
     */
    private array $loadedFromDraft = [];

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
     * Returns from local cache a synchronized regular entity instance if it is already synchronized.
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
        $entityClass = ClassUtils::getClass($entity);
        $draftSessionUuid ??= $this->draftSessionUuidProvider->getDraftSessionUuid();
        $splObjHash = spl_object_hash($entity);

        if (!isset($this->loadedFromDraft[$draftSessionUuid][$entityClass][$splObjHash])) {
            $this->loadedFromDraft[$draftSessionUuid][$entityClass][$splObjHash] =
                $this->entityDraftManager->loadFromEntityDraft($entity, $draftSessionUuid);
        }

        return $this->loadedFromDraft[$draftSessionUuid][$entityClass][$splObjHash];
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

    #[\Override]
    public function reset(): void
    {
        $this->loadedFromDraft = [];
    }
}
