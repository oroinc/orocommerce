<?php

namespace Oro\Bundle\SaleBundle\Acl\Voter;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * On the storefront, denies access to not available on the storefront quotes.
 */
class FrontendQuotePermissionVoter implements VoterInterface
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
        if (!$subject instanceof Quote || !$this->frontendHelper->isFrontendRequest()) {
            return self::ACCESS_ABSTAIN;
        }

        return $subject->isAvailableOnFrontend()
            ? self::ACCESS_GRANTED
            : self::ACCESS_DENIED;
    }
}
