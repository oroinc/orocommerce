<?php

namespace Oro\Bundle\SaleBundle\Acl\Voter;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * On the storefront, denies access to QuoteDemand entities that does not belong
 * to the logged in customer user or current visitor.
 * @see \Oro\Bundle\SaleBundle\Acl\AccessRule\FrontendQuoteDemandAccessRule
 */
class FrontendQuoteDemandPermissionVoter implements VoterInterface
{
    private FrontendHelper $frontendHelper;

    public function __construct(FrontendHelper $frontendHelper)
    {
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $subject, array $attributes)
    {
        if (!$subject instanceof QuoteDemand || !$this->frontendHelper->isFrontendRequest()) {
            return self::ACCESS_ABSTAIN;
        }

        $vote = self::ACCESS_ABSTAIN;
        foreach ($attributes as $attribute) {
            if (BasicPermission::VIEW !== $attribute) {
                continue;
            }

            $vote = self::ACCESS_DENIED;
            if ($this->isGranted($subject, $token)) {
                return self::ACCESS_GRANTED;
            }
        }

        return $vote;
    }

    protected function isGranted(QuoteDemand $quoteDemand, TokenInterface $token): bool
    {
        if ($token instanceof AnonymousCustomerUserToken) {
            $quoteVisitor = $quoteDemand->getVisitor();
            $tokenVisitor = $token->getVisitor();

            return
                null !== $quoteVisitor
                && null !== $tokenVisitor
                && $quoteVisitor->getId() === $tokenVisitor->getId();
        }

        $user = $token->getUser();
        if ($user instanceof CustomerUser) {
            $quoteUser = $quoteDemand->getCustomerUser();

            return
                null !== $quoteUser
                && $quoteUser->getId() === $user->getId();
        }

        return false;
    }
}
