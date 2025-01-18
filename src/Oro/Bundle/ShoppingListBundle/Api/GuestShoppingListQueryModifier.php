<?php

namespace Oro\Bundle\ShoppingListBundle\Api;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Util\QueryModifierEntityJoinTrait;
use Oro\Bundle\ApiBundle\Util\QueryModifierInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Modifies query builders for the following entities to filter entities belongs to the current visitor
 * when the current security context represents a visitor and the checkout feature is enabled for visitors:
 * shopping list
 * shopping list line item
 * shopping list product kit item line item
 */
class GuestShoppingListQueryModifier implements QueryModifierInterface
{
    use QueryModifierEntityJoinTrait;

    public function __construct(
        private readonly EntityClassResolver $entityClassResolver,
        private readonly TokenStorageInterface $tokenStorage
    ) {
    }

    #[\Override]
    public function modify(QueryBuilder $qb, bool $skipRootEntity): void
    {
        if ($skipRootEntity) {
            return;
        }

        $currentUser = $this->getCurrentUser();
        if ($currentUser instanceof CustomerUser) {
            return;
        }

        /** @var Expr\From $from */
        foreach ($qb->getDQLPart('from') as $from) {
            $entityClass = $this->entityClassResolver->getEntityClass($from->getFrom());
            if (ShoppingList::class === $entityClass) {
                $this->applyShoppingListRootRestriction($qb, $from->getAlias(), $currentUser);
            } elseif (LineItem::class === $entityClass) {
                $this->applyShoppingListItemItemRootRestriction($qb, $from->getAlias(), $currentUser);
            } elseif (ProductKitItemLineItem::class === $entityClass) {
                $this->applyShoppingListKitItemLineItemRootRestriction($qb, $from->getAlias(), $currentUser);
            }
        }
    }

    private function getCurrentUser(): CustomerUser|CustomerVisitor|null
    {
        $currentUser = null;
        $token = $this->tokenStorage->getToken();
        if (null !== $token) {
            if ($token instanceof AnonymousCustomerUserToken) {
                $currentUser = $token->getVisitor();
            } else {
                $user = $token->getUser();
                if ($user instanceof CustomerUser) {
                    $currentUser = $user;
                }
            }
        }

        return $currentUser;
    }

    private function applyShoppingListRootRestriction(
        QueryBuilder $qb,
        string $rootAlias,
        ?CustomerVisitor $visitor
    ): void {
        if (null === $visitor) {
            // deny access to shopping lists
            $qb->andWhere('1 = 0');
        } else {
            $this->applyCustomerVisitorRootRestriction($qb, $rootAlias, $visitor);
        }
    }

    private function applyShoppingListItemItemRootRestriction(
        QueryBuilder $qb,
        string $rootAlias,
        ?CustomerVisitor $visitor
    ): void {
        if (null === $visitor) {
            // deny access to shopping list items
            $qb->andWhere('1 = 0');
        } else {
            $this->applyCustomerVisitorRootRestriction(
                $qb,
                $this->ensureEntityJoined($qb, 'shoppingList', $rootAlias . '.shoppingList'),
                $visitor
            );
        }
    }

    private function applyShoppingListKitItemLineItemRootRestriction(
        QueryBuilder $qb,
        string $rootAlias,
        ?CustomerVisitor $visitor
    ): void {
        if (null === $visitor) {
            // deny access to shopping list product kit item line items
            $qb->andWhere('1 = 0');
        } else {
            $shoppingListLineItemAlias = $this->ensureEntityJoined($qb, 'lineItem', $rootAlias . '.lineItem');
            $this->applyCustomerVisitorRootRestriction(
                $qb,
                $this->ensureEntityJoined($qb, 'shoppingList', $shoppingListLineItemAlias . '.shoppingList'),
                $visitor
            );
        }
    }

    private function applyCustomerVisitorRootRestriction(
        QueryBuilder $qb,
        string $shoppingListAlias,
        CustomerVisitor $currentVisitor
    ): void {
        $paramName = QueryBuilderUtil::generateParameterName('customerVisitor', $qb);
        $qb
            ->andWhere($qb->expr()->exists($this->getCustomerVisitorSubquery($qb, $shoppingListAlias, $paramName)))
            ->setParameter($paramName, $currentVisitor);
    }

    private function getCustomerVisitorSubquery(
        QueryBuilder $qb,
        string $shoppingListAlias,
        string $customerVisitorParamName
    ): string {
        return $qb->getEntityManager()->createQueryBuilder()
            ->from(CustomerVisitor::class, 'customerVisitor')
            ->select('1')
            ->where(\sprintf(
                'customerVisitor = :%s AND %s MEMBER OF customerVisitor.shoppingLists',
                $customerVisitorParamName,
                $shoppingListAlias
            ))
            ->getDQL();
    }
}
