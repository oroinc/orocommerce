<?php

namespace Oro\Bundle\CheckoutBundle\Api;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Util\QueryModifierInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Modifies a customer user and customer user address query builder
 * to filter customer users and customer user addresses belongs to the current visitor
 * when the current security context represents a visitor
 * and the checkout feature is enabled for visitors.
 */
class GuestCheckoutQueryModifier implements QueryModifierInterface
{
    private GuestCheckoutChecker $guestCheckoutChecker;
    private EntityClassResolver $entityClassResolver;

    public function __construct(
        GuestCheckoutChecker $guestCheckoutChecker,
        EntityClassResolver $entityClassResolver
    ) {
        $this->guestCheckoutChecker = $guestCheckoutChecker;
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function modify(QueryBuilder $qb, bool $skipRootEntity): void
    {
        if ($skipRootEntity) {
            return;
        }

        if (!$this->guestCheckoutChecker->isGuestWithEnabledCheckout()) {
            return;
        }

        /** @var Expr\From $from */
        foreach ($qb->getDQLPart('from') as $from) {
            $entityClass = $this->entityClassResolver->getEntityClass($from->getFrom());
            if (CustomerUser::class === $entityClass) {
                $this->applyCustomerUserRootRestriction(
                    $qb,
                    $from->getAlias(),
                    $this->guestCheckoutChecker->getVisitor()->getCustomerUser()
                );
            } elseif (CustomerUserAddress::class === $entityClass) {
                $this->applyCustomerUserAddressRootRestriction(
                    $qb,
                    $from->getAlias(),
                    $this->guestCheckoutChecker->getVisitor()->getCustomerUser()
                );
            }
        }
    }

    private function applyCustomerUserRootRestriction(
        QueryBuilder $qb,
        string $rootAlias,
        ?CustomerUser $guestCustomerUser
    ): void {
        QueryBuilderUtil::checkIdentifier($rootAlias);
        if (null === $guestCustomerUser) {
            // deny access to customer users
            $qb->andWhere('1 = 0');
        } else {
            $this->applyCustomerUserRestriction($qb, $rootAlias, $guestCustomerUser);
        }
    }

    private function applyCustomerUserAddressRootRestriction(
        QueryBuilder $qb,
        string $rootAlias,
        ?CustomerUser $guestCustomerUser
    ): void {
        if (null === $guestCustomerUser) {
            // deny access to customer user addresses
            $qb->andWhere('1 = 0');
        } else {
            $this->applyCustomerUserRestriction(
                $qb,
                $this->ensureCustomerUserJoined($qb, $rootAlias),
                $guestCustomerUser
            );
        }
    }

    private function applyCustomerUserRestriction(
        QueryBuilder $qb,
        string $customerUserAlias,
        CustomerUser $currentUser
    ): void {
        QueryBuilderUtil::checkIdentifier($customerUserAlias);
        $paramName = QueryBuilderUtil::generateParameterName('customerUser');
        $qb
            ->andWhere(sprintf('%s = :%s', $customerUserAlias, $paramName))
            ->setParameter($paramName, $currentUser);
    }

    private function ensureCustomerUserJoined(QueryBuilder $qb, string $rootAlias): string
    {
        $customerUserJoin = $this->getCustomerUserJoin($qb, $rootAlias);
        if (null !== $customerUserJoin) {
            return $customerUserJoin->getAlias();
        }

        $customerUserJoinAlias = 'customerUser';
        QueryBuilderUtil::checkIdentifier($rootAlias);
        $qb->innerJoin($rootAlias . '.frontendOwner', $customerUserJoinAlias);

        return $customerUserJoinAlias;
    }

    private function getCustomerUserJoin(QueryBuilder $qb, string $rootAlias): ?Expr\Join
    {
        $result = null;
        /** @var Expr\Join[] $joins */
        foreach ($qb->getDQLPart('join') as $joinGroupAlias => $joins) {
            if ($joinGroupAlias !== $rootAlias) {
                continue;
            }
            $expectedJoin = sprintf('%s.frontendOwner', $rootAlias);
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
