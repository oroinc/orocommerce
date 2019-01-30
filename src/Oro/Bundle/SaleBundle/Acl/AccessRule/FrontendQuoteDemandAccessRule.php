<?php

namespace Oro\Bundle\SaleBundle\Acl\AccessRule;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;

/**
 * Limits QuoteDemand entity by the logged in customer user or current visitor.
 */
class FrontendQuoteDemandAccessRule implements AccessRuleInterface
{
    /** @var FrontendHelper */
    private $frontendHelper;

    /** @var TokenAccessor */
    private $tokenAccessor;

    /**
     * @param FrontendHelper $frontendHelper
     * @param TokenAccessor $tokenAccessor
     */
    public function __construct(FrontendHelper $frontendHelper, TokenAccessor $tokenAccessor)
    {
        $this->frontendHelper = $frontendHelper;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(Criteria $criteria): bool
    {
        return
            $criteria->getEntityClass() === QuoteDemand::class
            && $this->frontendHelper->isFrontendRequest();
    }

    /**
     * {@inheritdoc}
     */
    public function process(Criteria $criteria): void
    {
        $token = $this->tokenAccessor->getToken();
        if ($token instanceof AnonymousCustomerUserToken) {
            $criteria->andExpression(
                new Comparison(new Path('visitor'), Comparison::EQ, $token->getVisitor()->getId())
            );
        } else {
            $user = $this->tokenAccessor->getUser();
            if ($user instanceof CustomerUser) {
                $criteria->andExpression(
                    new Comparison(new Path('customerUser'), Comparison::EQ, $user->getId())
                );
            }
        }
    }
}
