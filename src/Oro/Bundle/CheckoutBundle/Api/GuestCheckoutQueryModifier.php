<?php

namespace Oro\Bundle\CheckoutBundle\Api;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Util\QueryModifierEntityJoinTrait;
use Oro\Bundle\ApiBundle\Util\QueryModifierInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Modifies query builders for the following entities to filter entities belongs to the current visitor
 * when the current security context represents a visitor and the checkout feature is enabled for visitors:
 * * customer
 * * customer user
 * * customer user address
 */
class GuestCheckoutQueryModifier implements QueryModifierInterface
{
    use QueryModifierEntityJoinTrait;

    public function __construct(
        private readonly GuestCheckoutChecker $guestCheckoutChecker,
        private readonly EntityClassResolver $entityClassResolver
    ) {
    }

    #[\Override]
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
            if (Customer::class === $entityClass) {
                $this->applyCustomerRootRestriction(
                    $qb,
                    $from->getAlias(),
                    $this->guestCheckoutChecker->getVisitor()->getCustomerUser()
                );
            } elseif (CustomerUser::class === $entityClass) {
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

    private function applyCustomerRootRestriction(
        QueryBuilder $qb,
        string $rootAlias,
        ?CustomerUser $guestCustomerUser
    ): void {
        if (null === $guestCustomerUser) {
            // deny access to customers
            $qb->andWhere('1 = 0');
        } else {
            $this->applyCustomerUserRestriction(
                $qb,
                $this->ensureEntityJoined($qb, 'customerUsers', $rootAlias . '.users'),
                $guestCustomerUser
            );
        }
    }

    private function applyCustomerUserRootRestriction(
        QueryBuilder $qb,
        string $rootAlias,
        ?CustomerUser $guestCustomerUser
    ): void {
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
                $this->ensureEntityJoined($qb, 'customerUser', $rootAlias . '.frontendOwner'),
                $guestCustomerUser
            );
        }
    }

    private function applyCustomerUserRestriction(
        QueryBuilder $qb,
        string $customerUserAlias,
        CustomerUser $currentUser
    ): void {
        $paramName = QueryBuilderUtil::generateParameterName('customerUser', $qb);
        $qb
            ->andWhere($qb->expr()->eq($customerUserAlias, ':' . $paramName))
            ->setParameter($paramName, $currentUser);
    }
}
