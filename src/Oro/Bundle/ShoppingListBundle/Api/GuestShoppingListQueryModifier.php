<?php

namespace Oro\Bundle\ShoppingListBundle\Api;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Util\QueryModifierInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Modifies a shopping list and shopping list item query builder to filter shopping lists and shopping list items
 * that belongs to the current customer visitor.
 */
class GuestShoppingListQueryModifier implements QueryModifierInterface
{
    private EntityClassResolver $entityClassResolver;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        EntityClassResolver $entityClassResolver,
        TokenStorageInterface $tokenStorage
    ) {
        $this->entityClassResolver = $entityClassResolver;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritDoc}
     */
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
        QueryBuilderUtil::checkIdentifier($rootAlias);
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
                $this->ensureShoppingListJoined($qb, $rootAlias),
                $visitor
            );
        }
    }

    private function applyCustomerVisitorRootRestriction(
        QueryBuilder $qb,
        string $shoppingListAlias,
        CustomerVisitor $currentVisitor
    ): void {
        QueryBuilderUtil::checkIdentifier($shoppingListAlias);
        $paramName = QueryBuilderUtil::generateParameterName('customerVisitor');
        $qb
            ->andWhere($qb->expr()->exists($this->getCustomerVisitorSubquery($qb, $shoppingListAlias, $paramName)))
            ->setParameter($paramName, $currentVisitor);
    }

    private function getCustomerVisitorSubquery(
        QueryBuilder $qb,
        string $shoppingListAlias,
        string $customerVisitorParamName
    ): string {
        QueryBuilderUtil::checkIdentifier($customerVisitorParamName);
        QueryBuilderUtil::checkIdentifier($shoppingListAlias);
        return $qb->getEntityManager()->createQueryBuilder()
            ->from(CustomerVisitor::class, 'customerVisitor')
            ->select('1')
            ->where(sprintf(
                'customerVisitor = :%s AND %s MEMBER OF customerVisitor.shoppingLists',
                $customerVisitorParamName,
                $shoppingListAlias
            ))
            ->getDQL();
    }

    private function ensureShoppingListJoined(QueryBuilder $qb, string $rootAlias): string
    {
        $shoppingListJoin = $this->getShoppingListJoin($qb, $rootAlias);
        if (null !== $shoppingListJoin) {
            return $shoppingListJoin->getAlias();
        }

        $shoppingListJoinAlias = 'shoppingList';
        QueryBuilderUtil::checkIdentifier($rootAlias);
        $qb->innerJoin($rootAlias . '.shoppingList', $shoppingListJoinAlias);

        return $shoppingListJoinAlias;
    }

    private function getShoppingListJoin(QueryBuilder $qb, string $rootAlias): ?Expr\Join
    {
        $result = null;
        /** @var Expr\Join[] $joins */
        foreach ($qb->getDQLPart('join') as $joinGroupAlias => $joins) {
            if ($joinGroupAlias !== $rootAlias) {
                continue;
            }
            $expectedJoin = sprintf('%s.shoppingList', $rootAlias);
            foreach ($joins as $key => $join) {
                if ($join->getJoin() === $expectedJoin) {
                    if ($join->getJoinType() === Expr\Join::LEFT_JOIN) {
                        $join = new Expr\Join(
                            Expr\Join::INNER_JOIN,
                            $join->getJoin(),
                            $join->getAlias(),
                            $join->getConditionType(),
                            $join->getCondition(),
                            $join->getIndexBy()
                        );
                        $joins[$key] = $join;
                        $joinDqlPart = [$joinGroupAlias => $joins];
                        $qb->add('join', $joinDqlPart);
                    }
                    $result = $join;
                    break;
                }
            }
        }

        return $result;
    }
}
