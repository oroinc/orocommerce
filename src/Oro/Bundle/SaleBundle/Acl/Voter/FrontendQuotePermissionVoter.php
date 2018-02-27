<?php

namespace Oro\Bundle\SaleBundle\Acl\Voter;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\FrontendBundle\Provider\ActionCurrentApplicationProvider as ApplicationProvider;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Checks if given Quote contains frontend internal status.
 * Triggered only for Commerce Application
 */
class FrontendQuotePermissionVoter extends Voter
{
    /** @var CurrentApplicationProviderInterface */
    protected $applicationProvider;

    /**
     * @param CurrentApplicationProviderInterface $applicationProvider
     */
    public function __construct(CurrentApplicationProviderInterface $applicationProvider)
    {
        $this->applicationProvider = $applicationProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        return $subject instanceof Quote && $this->isValidApplication();
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /* @var $subject Quote */
        return !$subject->getInternalStatus() ||
            in_array($subject->getInternalStatus()->getId(), Quote::FRONTEND_INTERNAL_STATUSES, true);
    }

    /**
     * @return bool
     */
    protected function isValidApplication()
    {
        return $this->applicationProvider->getCurrentApplication() === ApplicationProvider::COMMERCE_APPLICATION;
    }
}
