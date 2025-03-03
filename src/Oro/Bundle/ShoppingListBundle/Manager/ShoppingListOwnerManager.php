<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SecurityBundle\AccessRule\AclAccessRule;
use Oro\Bundle\SecurityBundle\AccessRule\AvailableOwnerAccessRule;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class that allows to set owner to shopping list.
 */
class ShoppingListOwnerManager
{
    public function __construct(
        private AclHelper $aclHelper,
        private ManagerRegistry $doctrine
    ) {
    }

    public function setOwner(int $ownerId, ShoppingList $shoppingList): void
    {
        /** @var CustomerUser $user */
        $user = $this->doctrine->getRepository(CustomerUser::class)->find($ownerId);
        if (null === $user) {
            throw new \InvalidArgumentException(\sprintf('User with id=%s not exists', $ownerId));
        }
        if ($user === $shoppingList->getCustomerUser()) {
            return;
        }
        if ($this->isUserAssignable($ownerId)) {
            $this->assignLineItems($shoppingList, $user);
            $shoppingList->setCustomerUser($user);

            $this->doctrine->getManagerForClass(ShoppingList::class)->flush();
        } else {
            throw new AccessDeniedException();
        }
    }

    private function isUserAssignable(int $id): bool
    {
        /** @var EntityRepository $repository */
        $repository = $this->doctrine->getRepository(CustomerUser::class);
        $qb = $repository
            ->createQueryBuilder('user')
            ->select('user.id')
            ->where('user.id = :id')
            ->setParameter('id', $id);

        $query = $this->aclHelper->apply(
            $qb,
            'ASSIGN',
            [
                AclAccessRule::DISABLE_RULE => true,
                AvailableOwnerAccessRule::ENABLE_RULE => true,
                AvailableOwnerAccessRule::TARGET_ENTITY_CLASS => ShoppingList::class
            ]
        );

        return null !== $query->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }

    private function assignLineItems(ShoppingList $shoppingList, CustomerUser $user): void
    {
        foreach ($shoppingList->getLineItems() as $lineItem) {
            $lineItem->setCustomerUser($user);
        }
    }
}
