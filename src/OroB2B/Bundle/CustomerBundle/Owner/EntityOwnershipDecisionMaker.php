<?php

namespace OroB2B\Bundle\CustomerBundle\Owner;

use Oro\Bundle\SecurityBundle\Owner\AbstractEntityOwnershipDecisionMaker;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

/**
 * This class implements AccessLevelOwnershipDecisionMakerInterface interface and allows to make ownership related
 * decisions using the tree of owners.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityOwnershipDecisionMaker extends AbstractEntityOwnershipDecisionMaker
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     *
     * @return $this
     */
    public function setSecurityFacade(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function supports()
    {
        return $this->securityFacade && $this->securityFacade->getLoggedUser() instanceof AccountUser;
    }
}
