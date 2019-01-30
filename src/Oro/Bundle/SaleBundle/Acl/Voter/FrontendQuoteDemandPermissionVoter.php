<?php

namespace Oro\Bundle\SaleBundle\Acl\Voter;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Limits QuoteDemand entity by the logged in customer user or current visitor.
 * @see \Oro\Bundle\SaleBundle\Acl\AccessRule\FrontendQuoteDemandAccessRule
 */
class FrontendQuoteDemandPermissionVoter extends Voter
{
    /** @var FrontendHelper */
    private $frontendHelper;

    /**
     * @param FrontendHelper $frontendHelper
     */
    public function __construct(FrontendHelper $frontendHelper)
    {
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        return $attribute === BasicPermissionMap::PERMISSION_VIEW &&
            $subject instanceof QuoteDemand &&
            $this->frontendHelper->isFrontendRequest();
    }

    /**
     * {@inheritdoc}
     * @var QuoteDemand $subject
     * @var AnonymousCustomerUserToken $token
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if ($token instanceof AnonymousCustomerUserToken) {
            $quoteVisitor = $subject->getVisitor();
            $tokenVisitor = $token->getVisitor();

            return $quoteVisitor && $tokenVisitor && $quoteVisitor->getId() === $tokenVisitor->getId();
        }

        $tokenUser = $token->getUser();
        if ($tokenUser instanceof CustomerUser) {
            $quoteUser = $subject->getCustomerUser();

            return $quoteUser && $quoteUser->getId() === $tokenUser->getId();
        }

        return false;
    }
}
