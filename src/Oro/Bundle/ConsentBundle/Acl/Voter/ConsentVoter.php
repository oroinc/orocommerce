<?php

namespace Oro\Bundle\ConsentBundle\Acl\Voter;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Entity\Repository\ConsentAcceptanceRepository;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

/**
 * Disables deleting and editing the consent in case it was accepted by any user
 */
class ConsentVoter extends AbstractEntityVoter
{
    const ATTRIBUTE_EDIT = 'EDIT';
    const ATTRIBUTE_DELETE = 'DELETE';

    /** @var array */
    protected $supportedAttributes = [
        self::ATTRIBUTE_EDIT,
        self::ATTRIBUTE_DELETE
    ];

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        /** @var ConsentAcceptanceRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(ConsentAcceptance::class);

        /** @var Consent $consent */
        $consent = $this->doctrineHelper->getEntityReference($this->className, $identifier);

        if ($repository->hasConsentAcceptancesByConsent($consent)) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }
}
