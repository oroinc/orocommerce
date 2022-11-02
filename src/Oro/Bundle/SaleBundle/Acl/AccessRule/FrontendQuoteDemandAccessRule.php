<?php

namespace Oro\Bundle\SaleBundle\Acl\AccessRule;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Denies access to QuoteDemand entities that does not belong to the logged in customer user or current visitor.
 * This access rule is intended to be used on the storefront only.
 */
class FrontendQuoteDemandAccessRule implements AccessRuleInterface
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(Criteria $criteria): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Criteria $criteria): void
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return;
        }

        if ($token instanceof AnonymousCustomerUserToken) {
            $criteria->andExpression(
                new Comparison(new Path('visitor'), Comparison::EQ, $token->getVisitor()->getId())
            );
        } else {
            $user = $token->getUser();
            if ($user instanceof CustomerUser) {
                $criteria->andExpression(
                    new Comparison(new Path('customerUser'), Comparison::EQ, $user->getId())
                );
            }
        }
    }
}
