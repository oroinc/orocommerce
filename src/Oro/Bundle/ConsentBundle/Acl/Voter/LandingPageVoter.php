<?php

namespace Oro\Bundle\ConsentBundle\Acl\Voter;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

/**
 * Disables deleting the landing page if it has accepted consents associated with it.
 */
class LandingPageVoter extends AbstractEntityVoter
{
    /** {@inheritDoc} */
    protected $supportedAttributes = [BasicPermission::EDIT, BasicPermission::DELETE];

    /**
     * {@inheritDoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        /** @var Page $page */
        $page = $this->doctrineHelper->getEntityReference($this->className, $identifier);

        return $this->getConsentAcceptanceRepository()->hasLandingPageAcceptedConsents($page)
            ? self::ACCESS_DENIED
            : self::ACCESS_ABSTAIN;
    }

    private function getConsentAcceptanceRepository(): ConsentAcceptanceRepository
    {
        return $this->doctrineHelper->getEntityRepository(ConsentAcceptance::class);
    }
}
