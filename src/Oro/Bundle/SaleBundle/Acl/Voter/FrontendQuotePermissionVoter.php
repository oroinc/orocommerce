<?php

namespace Oro\Bundle\SaleBundle\Acl\Voter;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Checks if given Quote contains frontend internal status.
 * Triggered only for Commerce Application
 */
class FrontendQuotePermissionVoter extends Voter
{
    /** @var FrontendHelper */
    private $frontendHelper;

    public function __construct(FrontendHelper $frontendHelper)
    {
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        return $subject instanceof Quote && $this->frontendHelper->isFrontendRequest();
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var $subject Quote */
        return $subject->isAvailableOnFrontend();
    }
}
