<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\DraftSession\Factory;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Provider\RfqCurrencyProvider;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Factory\EntityDraftFactoryInterface;

/**
 * Creates an Order draft from an RFQ Request, mapping all matching fields and relations.
 */
class OrderDraftFromRfqFactory implements EntityDraftFactoryInterface
{
    public function __construct(
        private readonly EntityDraftSyncReferenceResolver $draftSyncReferenceResolver,
        private readonly RfqCurrencyProvider $rfqCurrencyProvider,
        private readonly WebsiteManager $websiteManager,
    ) {
    }

    #[\Override]
    public function supports(string $entityClass): bool
    {
        return is_a($entityClass, Request::class, true);
    }

    #[\Override]
    public function createDraft(EntityDraftAwareInterface $entity, string $draftSessionUuid): Order
    {
        assert($entity instanceof Request);

        $orderDraft = new Order();
        $orderDraft->setDraftSessionUuid($draftSessionUuid);

        $this->synchronizeFields($entity, $orderDraft);

        return $orderDraft;
    }

    private function synchronizeFields(Request $request, Order $orderDraft): void
    {
        if ($request->getOrganization()) {
            $orderDraft->setOrganization($this->getReference($request->getOrganization()));
        }

        $orderDraft->setCustomer($this->getReference($request->getCustomer()));
        $orderDraft->setCustomerUser($this->getReference($request->getCustomerUser()));
        $orderDraft->setPoNumber($request->getPoNumber());

        $website = $request->getWebsite() ?? $this->websiteManager->getDefaultWebsite();
        if ($website !== null) {
            $orderDraft->setWebsite($this->getReference($website));
        }

        $orderDraft->setCurrency($this->rfqCurrencyProvider->getRfqCurrency($request));

        if ($request->getShipUntil()) {
            $orderDraft->setShipUntil(clone $request->getShipUntil());
        }

        $orderDraft->setCustomerNotes($request->getNote());

        $orderDraft->setSourceEntityClass(ClassUtils::getClass($request));
        $orderDraft->setSourceEntityId($request->getId());
        $orderDraft->setSourceEntityIdentifier($request->getIdentifier());
    }

    private function getReference(?object $entity): ?object
    {
        return $this->draftSyncReferenceResolver->getReference($entity);
    }
}
