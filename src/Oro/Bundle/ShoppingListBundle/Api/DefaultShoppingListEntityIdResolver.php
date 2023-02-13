<?php

namespace Oro\Bundle\ShoppingListBundle\Api;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Request\EntityIdResolverInterface;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Resolves "default" identifier for ShoppingList entity.
 * This identifier can be used to identify a default shopping list
 * for the current authenticated customer user or the current customer visitor.
 */
class DefaultShoppingListEntityIdResolver implements EntityIdResolverInterface
{
    private TokenStorageInterface $tokenStorage;
    private CurrentShoppingListStorage $currentShoppingListStorage;
    private DoctrineHelper $doctrineHelper;
    private AclHelper $aclHelper;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        CurrentShoppingListStorage $currentShoppingListStorage,
        DoctrineHelper $doctrineHelper,
        AclHelper $aclHelper
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->currentShoppingListStorage = $currentShoppingListStorage;
        $this->doctrineHelper = $doctrineHelper;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return <<<MARKDOWN
**default** can be used to identify the default shopping list.
MARKDOWN;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(): mixed
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return null;
        }

        $defaultShoppingListId = null;
        if ($token instanceof AnonymousCustomerUserToken) {
            $visitor = $token->getVisitor();
            if (null !== $visitor) {
                $defaultShoppingListId = $this->getDefaultShoppingListIdForVisitor($visitor->getId());
            }
        } else {
            $user = $token->getUser();
            if ($user instanceof CustomerUser) {
                $defaultShoppingListId = $this->getDefaultShoppingListIdForCustomerUser($user->getId());
            }
        }

        return $defaultShoppingListId;
    }

    private function getDefaultShoppingListIdForCustomerUser(int $customerUserId): ?int
    {
        $defaultShoppingListId = $this->currentShoppingListStorage->get($customerUserId);
        if (null === $defaultShoppingListId) {
            $qb = $this->getDefaultShoppingListQueryBuilder()
                ->where('e.customerUser = :userId')
                ->setParameter('userId', $customerUserId);
            $rows = $this->aclHelper->apply($qb)->getArrayResult();
            if ($rows) {
                $defaultShoppingListId = $rows[0]['id'];
            }
        }

        return $defaultShoppingListId;
    }

    private function getDefaultShoppingListIdForVisitor(int $visitorId): ?int
    {
        $qb = $this->getDefaultShoppingListQueryBuilder();
        $visitorSubquery = $qb->getEntityManager()->createQueryBuilder()
            ->from(CustomerVisitor::class, 'customerVisitor')
            ->select('1')
            ->where('customerVisitor = :visitorId AND e MEMBER OF customerVisitor.shoppingLists')
            ->getDQL();
        $qb
            ->where($qb->expr()->exists($visitorSubquery))
            ->setParameter('visitorId', $visitorId);
        $rows = $this->aclHelper->apply($qb)->getArrayResult();
        if (!$rows) {
            return null;
        }

        return $rows[0]['id'];
    }

    private function getDefaultShoppingListQueryBuilder(): QueryBuilder
    {
        return $this->doctrineHelper
            ->createQueryBuilder(ShoppingList::class, 'e')
            ->select('e.id')
            ->addOrderBy('e.id', 'DESC')
            ->setMaxResults(1);
    }
}
