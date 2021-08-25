<?php

namespace Oro\Bundle\ConsentBundle\Acl\Voter;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

/**
 * Disables deleting and editing the consent in case it was accepted by any user.
 */
class ConsentVoter extends AbstractEntityVoter
{
    /** {@inheritDoc} */
    protected $supportedAttributes = [BasicPermission::EDIT, BasicPermission::DELETE];

    /**
     * {@inheritDoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        /** @var Consent $consent */
        $consent = $this->doctrineHelper->getEntityReference($this->className, $identifier);

        return $this->getConsentAcceptanceRepository()->hasConsentAcceptancesByConsent($consent)
            ? self::ACCESS_DENIED
            : self::ACCESS_ABSTAIN;
    }

    private function getConsentAcceptanceRepository(): ConsentAcceptanceRepository
    {
        return $this->doctrineHelper->getEntityRepository(ConsentAcceptance::class);
    }
}
