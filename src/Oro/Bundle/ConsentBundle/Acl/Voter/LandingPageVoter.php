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
    /**
     * @var array
     */
    protected $supportedAttributes = [BasicPermission::EDIT, BasicPermission::DELETE];

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        /** @var ConsentAcceptanceRepository $consentAcceptanceRepository */
        $consentAcceptanceRepository = $this->doctrineHelper->getEntityRepository(ConsentAcceptance::class);
        /** @var Page $page */
        $page = $this->doctrineHelper->getEntityReference($this->className, $identifier);

        if ($consentAcceptanceRepository->hasLandingPageAcceptedConsents($page)) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }
}
