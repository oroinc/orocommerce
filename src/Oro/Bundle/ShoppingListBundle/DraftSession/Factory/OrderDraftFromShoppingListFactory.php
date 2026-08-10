<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\DraftSession\Factory;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Factory\EntityDraftFactoryInterface;

/**
 * Creates an Order draft from a Shopping List, mapping all matching fields and relations.
 */
class OrderDraftFromShoppingListFactory implements EntityDraftFactoryInterface
{
    public function __construct(
        private readonly EntityDraftSyncReferenceResolver $draftSyncReferenceResolver,
        private readonly WebsiteManager $websiteManager,
    ) {
    }

    #[\Override]
    public function supports(string $entityClass): bool
    {
        return is_a($entityClass, ShoppingList::class, true);
    }

    #[\Override]
    public function createDraft(EntityDraftAwareInterface $entity, string $draftSessionUuid): Order
    {
        assert($entity instanceof ShoppingList);

        $orderDraft = new Order();
        $orderDraft->setDraftSessionUuid($draftSessionUuid);

        $this->synchronizeFields($entity, $orderDraft);

        return $orderDraft;
    }

    private function synchronizeFields(ShoppingList $shoppingList, Order $orderDraft): void
    {
        if ($shoppingList->getOrganization()) {
            $orderDraft->setOrganization($this->getReference($shoppingList->getOrganization()));
        }

        $orderDraft->setCustomer($this->getReference($shoppingList->getCustomer()));
        $orderDraft->setCustomerUser($this->getReference($shoppingList->getCustomerUser()));

        $website = $shoppingList->getWebsite() ?? $this->websiteManager->getDefaultWebsite();
        if ($website !== null) {
            $orderDraft->setWebsite($this->getReference($website));
        }

        $orderDraft->setCurrency($shoppingList->getCurrency());

        $orderDraft->setCustomerNotes($shoppingList->getNotes());

        $orderDraft->setSourceEntityClass(ClassUtils::getClass($shoppingList));
        $orderDraft->setSourceEntityId($shoppingList->getId());
        $orderDraft->setSourceEntityIdentifier($shoppingList->getIdentifier());
    }

    private function getReference(?object $entity): ?object
    {
        return $this->draftSyncReferenceResolver->getReference($entity);
    }
}
